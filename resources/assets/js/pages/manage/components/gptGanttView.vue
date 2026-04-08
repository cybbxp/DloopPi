<script>
import dayjs from "dayjs";

export default {
  name: "GanttView",

  props: {
    cacheTasks: Array,
    cacheProjects: Array,
    year: Number,
  },

  data() {
    return {
      dayWidth: 24,
      rowHeight: 36,
      expandedTasks: new Set(),

      // 拖拽预览
      dragPreview: null,
      milestonePreview: null,

      scrollTop: 0,
      scrollLeft: 0,

      // 内部缓存（新增）
      _taskTimestampCache: new Map(),
    };
  },

  computed: {
    /* ==============================
     * ① 时间基础数据（只算一次）
     * ============================== */

    yearStart() {
      return dayjs(`${this.year}-01-01`);
    },

    yearEnd() {
      return dayjs(`${this.year}-12-31`);
    },

    visibleDays() {
      const days = [];
      let cur = this.yearStart;
      while (cur.isBefore(this.yearEnd) || cur.isSame(this.yearEnd)) {
        days.push(cur);
        cur = cur.add(1, "day");
      }
      return days;
    },

    totalWidth() {
      return this.visibleDays.length * this.dayWidth;
    },

    /* ==============================
     * ② 任务时间戳缓存层（解决 dayjs 重复创建）
     * ============================== */

    taskTimestampMap() {
      const map = new Map();

      (this.cacheTasks || []).forEach((task) => {
        const start = dayjs(task.start_at).valueOf();
        const end = dayjs(task.end_at).valueOf();

        map.set(task.id, {
          start,
          end,
          duration: Math.max(1, dayjs(task.end_at).diff(task.start_at, "day") + 1),
        });
      });

      return map;
    },

    /* ==============================
     * ③ childrenMap（O(1) 子任务查找）
     * ============================== */

    childrenMap() {
      const map = new Map();

      (this.cacheTasks || []).forEach((task) => {
        const parent = task.parent_id || 0;
        if (!map.has(parent)) {
          map.set(parent, []);
        }
        map.get(parent).push(task);
      });

      return map;
    },

    /* ==============================
     * ④ 构建纯树结构（不做像素计算）
     * ============================== */

    taskTree() {
      const build = (parentId = 0, level = 0) => {
        const children = this.childrenMap.get(parentId) || [];

        return children.map((task) => {
          return {
            ...task,
            _level: level,
            children: build(task.id, level + 1),
          };
        });
      };

      return build(0, 0);
    },
        /* ==============================
     * ⑤ 展开控制层（只负责扁平化）
     * ============================== */

    flatTasks() {
      const result = [];

      const traverse = (nodes) => {
        nodes.forEach((node) => {
          result.push(node);

          if (
            node.children &&
            node.children.length &&
            this.expandedTasks.has(node.id)
          ) {
            traverse(node.children);
          }
        });
      };

      traverse(this.taskTree);

      return result;
    },

    /* ==============================
     * ⑥ 布局层（只计算 rowIndex / 高度）
     * ============================== */

    layoutTasks() {
      return this.flatTasks.map((task, index) => {
        return {
          ...task,
          _rowIndex: index,
          _top: index * this.rowHeight,
        };
      });
    },

    totalHeight() {
      return this.layoutTasks.length * this.rowHeight;
    },

    /* ==============================
     * ⑦ 时间映射层（只负责像素计算）
     * ============================== */

    timeMapping() {
      const map = new Map();
      const yearStartTs = this.yearStart.valueOf();

      this.layoutTasks.forEach((task) => {
        const ts = this.taskTimestampMap.get(task.id);
        if (!ts) return;

        const startOffsetDays =
          dayjs(ts.start).diff(yearStartTs, "day");

        map.set(task.id, {
          startOffsetPx: startOffsetDays * this.dayWidth,
          durationPx: ts.duration * this.dayWidth,
        });
      });

      return map;
    },

    /* ==============================
     * ⑧ 最终 projects（给模板用）
     *    —— 轻量封装层
     * ============================== */

    projects() {
      return this.layoutTasks.map((task) => {
        const time = this.timeMapping.get(task.id) || {
          startOffsetPx: 0,
          durationPx: 0,
        };

        return {
          ...task,
          _startOffset: time.startOffsetPx,
          _duration: time.durationPx,
          _height: this.rowHeight,
        };
      });
    },
        /* ==============================
     * ⑨ Milestone 纯计算层
     * ============================== */

    milestoneMap() {
      const map = new Map();

      this.layoutTasks.forEach((task) => {
        if (!task.milestones || !task.milestones.length) return;

        const arr = task.milestones.map((m, index) => {
          const ts = dayjs(m.date).valueOf();
          const offsetDays = dayjs(ts).diff(this.yearStart, "day");

          return {
            ...m,
            _index: index,
            _taskId: task.id,
            _left: offsetDays * this.dayWidth,
          };
        });

        map.set(task.id, arr);
      });

      return map;
    },
  },

  methods: {
    /* ==============================
     * ⑩ Milestone 获取接口（模板兼容）
     * ============================== */

    getMilestones(task) {
      const base = this.milestoneMap.get(task.id) || [];

      // 如果当前有拖拽预览，覆盖对应里程碑
      if (
        this.milestonePreview &&
        this.milestonePreview.taskId === task.id
      ) {
        return base.map((m) => {
          if (m._index === this.milestonePreview.index) {
            return {
              ...m,
              _left: this.milestonePreview.left,
            };
          }
          return m;
        });
      }

      return base;
    },

    /* ==============================
     * ⑪ Milestone 拖拽开始
     * ============================== */

    handleMilestoneDragStart(task, milestone, event) {
      this.milestonePreview = {
        taskId: task.id,
        index: milestone._index,
        startX: event.clientX,
        originalLeft: milestone._left,
        left: milestone._left,
      };

      document.addEventListener(
        "mousemove",
        this.handleMilestoneDragging
      );
      document.addEventListener(
        "mouseup",
        this.handleMilestoneDragEnd
      );
    },

    /* ==============================
     * ⑫ Milestone 拖拽中
     * ============================== */

    handleMilestoneDragging(event) {
      if (!this.milestonePreview) return;

      const delta =
        event.clientX - this.milestonePreview.startX;

      this.milestonePreview.left =
        this.milestonePreview.originalLeft + delta;
    },

    /* ==============================
     * ⑬ Milestone 拖拽结束
     * ============================== */

    handleMilestoneDragEnd() {
      if (!this.milestonePreview) return;

      const preview = this.milestonePreview;

      const dayOffset = Math.round(
        preview.left / this.dayWidth
      );

      const newDate = this.yearStart
        .add(dayOffset, "day")
        .format("YYYY-MM-DD");

      // 更新真实数据（只在这里改）
      const task = this.cacheTasks.find(
        (t) => t.id === preview.taskId
      );

      if (
        task &&
        task.milestones &&
        task.milestones[preview.index]
      ) {
        task.milestones[preview.index].date = newDate;
      }

      this.milestonePreview = null;

      document.removeEventListener(
        "mousemove",
        this.handleMilestoneDragging
      );
      document.removeEventListener(
        "mouseup",
        this.handleMilestoneDragEnd
      );
    },
        /* ==============================
     * ⑭ 任务条拖拽
     * ============================== */

    handleTaskDragStart(task, event) {
      this.dragPreview = {
        taskId: task.id,
        startX: event.clientX,
        originalOffset: task._startOffset,
        offset: task._startOffset,
      };

      document.addEventListener("mousemove", this.handleTaskDragging);
      document.addEventListener("mouseup", this.handleTaskDragEnd);
    },

    handleTaskDragging(event) {
      if (!this.dragPreview) return;

      const delta = event.clientX - this.dragPreview.startX;
      this.dragPreview.offset =
        this.dragPreview.originalOffset + delta;
    },

    handleTaskDragEnd() {
      if (!this.dragPreview) return;

      const preview = this.dragPreview;

      const dayOffset = Math.round(
        preview.offset / this.dayWidth
      );

      const newStart = this.yearStart
        .add(dayOffset, "day");

      const ts = this.taskTimestampMap.get(preview.taskId);

      if (ts) {
        const durationDays = ts.duration - 1;
        const newEnd = newStart.add(durationDays, "day");

        const task = this.cacheTasks.find(
          (t) => t.id === preview.taskId
        );

        if (task) {
          task.start_at = newStart.format("YYYY-MM-DD");
          task.end_at = newEnd.format("YYYY-MM-DD");
        }
      }

      this.dragPreview = null;

      document.removeEventListener("mousemove", this.handleTaskDragging);
      document.removeEventListener("mouseup", this.handleTaskDragEnd);
    },

    /* ==============================
     * ⑮ Resize（右侧拉伸）
     * ============================== */

    handleResizeStart(task, event) {
      event.stopPropagation();

      this.dragPreview = {
        taskId: task.id,
        startX: event.clientX,
        originalDuration: task._duration,
        duration: task._duration,
        resizing: true,
      };

      document.addEventListener("mousemove", this.handleResizing);
      document.addEventListener("mouseup", this.handleResizeEnd);
    },

    handleResizing(event) {
      if (!this.dragPreview || !this.dragPreview.resizing) return;

      const delta = event.clientX - this.dragPreview.startX;

      const newDuration =
        this.dragPreview.originalDuration + delta;

      this.dragPreview.duration =
        Math.max(this.dayWidth, newDuration);
    },

    handleResizeEnd() {
      if (!this.dragPreview) return;

      const preview = this.dragPreview;

      const days = Math.round(
        preview.duration / this.dayWidth
      );

      const task = this.cacheTasks.find(
        (t) => t.id === preview.taskId
      );

      if (task) {
        const start = dayjs(task.start_at);
        task.end_at = start
          .add(days - 1, "day")
          .format("YYYY-MM-DD");
      }

      this.dragPreview = null;

      document.removeEventListener("mousemove", this.handleResizing);
      document.removeEventListener("mouseup", this.handleResizeEnd);
    },

    /* ==============================
     * ⑯ 展开 / 折叠
     * ============================== */

    toggleTask(task) {
      if (this.expandedTasks.has(task.id)) {
        this.expandedTasks.delete(task.id);
      } else {
        this.expandedTasks.add(task.id);
      }

      // 强制触发响应（Vue2 对 Set 不深度监听）
      this.expandedTasks = new Set(this.expandedTasks);
    },

    /* ==============================
     * ⑰ 鼠标滚轮缩放（围绕指针）
     * ============================== */

    handleWheel(event) {
      if (!event.ctrlKey) return;

      event.preventDefault();

      const rect = event.currentTarget.getBoundingClientRect();
      const mouseX = event.clientX - rect.left;

      const ratio =
        (this.scrollLeft + mouseX) / this.totalWidth;

      const delta = event.deltaY > 0 ? -2 : 2;

      const newWidth = Math.max(
        8,
        Math.min(80, this.dayWidth + delta)
      );

      this.dayWidth = newWidth;

      this.$nextTick(() => {
        this.scrollLeft =
          this.totalWidth * ratio - mouseX;
      });
    },

    /* ==============================
     * ⑱ 滚动同步
     * ============================== */

    handleScroll(event) {
      this.scrollTop = event.target.scrollTop;
      this.scrollLeft = event.target.scrollLeft;
    },
  },

  watch: {
    cacheTasks: {
      deep: true,
      handler() {
        // 任务变化时清空展开状态避免错位
        this.expandedTasks = new Set(this.expandedTasks);
      },
    },
  },

  mounted() {
    // 可在此添加初始化逻辑
  },

  beforeDestroy() {
    document.removeEventListener("mousemove", this.handleTaskDragging);
    document.removeEventListener("mouseup", this.handleTaskDragEnd);
    document.removeEventListener("mousemove", this.handleResizing);
    document.removeEventListener("mouseup", this.handleResizeEnd);
    document.removeEventListener("mousemove", this.handleMilestoneDragging);
    document.removeEventListener("mouseup", this.handleMilestoneDragEnd);
  },
};
</script>