<template>
  <div class="page-gantt">
    <div class="gantt-header">
      <div class="gantt-title">{{$L('甘特图')}}</div>
      <div class="gantt-controls">
        <Select v-model="selectedYear" style="width: 120px" @on-change="onYearChange">
          <Option v-for="year in availableYears" :key="year" :value="year">
            {{ year }} {{$L('年')}}
          </Option>
        </Select>
      </div>
    </div>

    <div class="gantt-content">
      <GanttView
        :year="selectedYear"
        @update-task="handleUpdateTask"
      />
    </div>
  </div>
</template>

<script>
import GanttView from './components/GanttView.vue'

export default {
  name: 'PageGantt',

  components: {
    GanttView
  },

  data() {
    return {
      selectedYear: new Date().getFullYear()
    }
  },

  computed: {
    availableYears() {
      const currentYear = new Date().getFullYear()
      const years = []
      for (let i = currentYear - 2; i <= currentYear + 2; i++) {
        years.push(i)
      }
      return years
    }
  },

  methods: {
    onYearChange(year) {
      this.selectedYear = year
    },

    handleUpdateTask(data) {
      this.$store.dispatch("taskUpdate", {
        task_id: data.id,
        times: [data.start_at, data.end_at]
      }).then(() => {
        $A.messageSuccess(this.$L('任务时间已更新'))
      }).catch(({ msg }) => {
        $A.modalError(msg)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page-gantt {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #fff;
}

.gantt-header {
  height: 60px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #e8e8e8;
  flex-shrink: 0;
}

.gantt-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.gantt-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.gantt-content {
  flex: 1;
  overflow: hidden;
}
</style>
