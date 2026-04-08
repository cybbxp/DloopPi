<template>
    <div class="asset-library-page">
        <!-- 顶部工具栏 -->
        <div class="toolbar">
            <div class="left-tools">
                <Select v-model="filters.project_id" placeholder="选择项目" style="width:200px" @on-change="handleProjectChange">
                    <Option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</Option>
                </Select>

                <Select v-model="filters.category_id" placeholder="资产分类" style="width:150px" @on-change="loadAssets">
                    <Option value="">全部分类</Option>
                    <Option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</Option>
                </Select>

                <Input
                    v-model="filters.keyword"
                    placeholder="搜索资产..."
                    style="width:250px"
                    search
                    @on-search="loadAssets"
                />
            </div>

            <div class="right-tools">
                <Button icon="md-settings" @click="showProjectSettings">项目配置</Button>
                <Button type="primary" icon="md-add" @click="showCreateModal">创建资产</Button>
            </div>
        </div>

        <!-- 资产网格 -->
        <div class="asset-grid" v-if="assets.length > 0">
            <div
                v-for="asset in assets"
                :key="asset.id"
                class="asset-card"
                @click="openAssetDetail(asset)"
            >
                <div class="thumbnail">
                    <img :src="asset.thumbnail_url || '/images/default-asset.png'" />
                    <div class="status-badge" :class="'status-' + asset.status">
                        {{ getStatusText(asset.status) }}
                    </div>
                </div>
                <div class="info">
                    <div class="name-row">
                        <div class="name" :title="asset.name">{{ asset.name }}</div>
                        <div class="type-buttons" @click.stop>
                            <Button
                                v-for="step in pipelineSteps.asset"
                                :key="`${asset.id}-${step.id}`"
                                size="small"
                                @click.stop="openAssetStepFolder(asset, step)"
                            >
                                {{ step.name }}
                            </Button>
                        </div>
                    </div>
                    <div class="code">{{ asset.code }}</div>
                    <div class="meta">
                        <Tag>{{ asset.category.name }}</Tag>
                        <span class="creator">{{ asset.creator ? asset.creator.nickname : '' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 空状态 -->
        <div v-else class="empty-state">
            <Icon type="ios-folder-open-outline" size="80" color="#ccc" />
            <p>暂无资产</p>
            <Button type="primary" @click="showCreateModal">创建第一个资产</Button>
        </div>

        <!-- 分页 -->
        <Page
            v-if="pagination.total > 0"
            :total="pagination.total"
            :current="pagination.current"
            :page-size="pagination.per_page"
            @on-change="handlePageChange"
            show-total
        />

        <!-- 创建资产弹窗 -->
        <Modal v-model="createModal.visible" title="创建资产" width="600">
            <Form :model="createModal.form" :label-width="100">
                <FormItem label="项目">
                    <Select v-model="createModal.form.project_id">
                        <Option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</Option>
                    </Select>
                </FormItem>

                <FormItem label="分类">
                    <Select v-model="createModal.form.category_id">
                        <Option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</Option>
                    </Select>
                </FormItem>

                <FormItem label="资产名称">
                    <Input v-model="createModal.form.name" placeholder="如：Hero" />
                </FormItem>

                <FormItem label="资产编码">
                    <Input v-model="createModal.form.code" placeholder="如：CH_HERO_001" />
                </FormItem>

                <FormItem label="描述">
                    <Input v-model="createModal.form.description" type="textarea" :rows="3" />
                </FormItem>

                <FormItem label="自动创建目录">
                    <i-switch v-model="createModal.form.create_structure" />
                </FormItem>
            </Form>

            <div slot="footer">
                <Button @click="createModal.visible = false">取消</Button>
                <Button type="primary" @click="handleCreateAsset" :loading="createModal.loading">创建</Button>
            </div>
        </Modal>

        <!-- 项目配置弹窗 -->
        <Modal v-model="settingsModal.visible" title="项目配置" width="800">
            <Tabs v-model="settingsModal.activeTab">
                <!-- 存储配置 -->
                <TabPane label="存储配置" name="storage">
                    <Form :model="settingsModal.form" :label-width="120">
                        <FormItem label="存储根路径">
                            <Input v-model="settingsModal.form.storage_root" placeholder="如：H:\ProjectA 或 /mnt/projects/ProjectA">
                                <template #append>
                                    <!-- Electron 环境：直接浏览按钮 -->
                                    <Button v-if="isElectron" icon="md-folder" @click="handleQuickPath">
                                        浏览
                                    </Button>
                                    <!-- Web 环境：快捷路径下拉菜单 -->
                                    <Dropdown v-else @on-click="handleQuickPath">
                                        <Button icon="md-folder">
                                            快捷路径
                                        </Button>
                                        <template #list>
                                            <DropdownMenu>
                                                <DropdownItem divided>Windows 盘符</DropdownItem>
                                                <DropdownItem name="C:\">C:\</DropdownItem>
                                                <DropdownItem name="D:\">D:\</DropdownItem>
                                                <DropdownItem name="E:\">E:\</DropdownItem>
                                                <DropdownItem name="F:\">F:\</DropdownItem>
                                                <DropdownItem name="G:\">G:\</DropdownItem>
                                                <DropdownItem name="H:\">H:\</DropdownItem>
                                                <DropdownItem name="I:\">I:\</DropdownItem>
                                                <DropdownItem name="J:\">J:\</DropdownItem>
                                                <DropdownItem name="K:\">K:\</DropdownItem>
                                                <DropdownItem name="L:\">L:\</DropdownItem>
                                                <DropdownItem name="M:\">M:\</DropdownItem>
                                                <DropdownItem name="N:\">N:\</DropdownItem>
                                                <DropdownItem name="O:\">O:\</DropdownItem>
                                                <DropdownItem name="P:\">P:\</DropdownItem>
                                                <DropdownItem name="Q:\">Q:\</DropdownItem>
                                                <DropdownItem name="R:\">R:\</DropdownItem>
                                                <DropdownItem name="S:\">S:\</DropdownItem>
                                                <DropdownItem name="T:\">T:\</DropdownItem>
                                                <DropdownItem name="U:\">U:\</DropdownItem>
                                                <DropdownItem name="V:\">V:\</DropdownItem>
                                                <DropdownItem name="W:\">W:\</DropdownItem>
                                                <DropdownItem name="X:\">X:\</DropdownItem>
                                                <DropdownItem name="Y:\">Y:\</DropdownItem>
                                                <DropdownItem name="Z:\">Z:\</DropdownItem>
                                                <DropdownItem divided>Linux 常用路径</DropdownItem>
                                                <DropdownItem name="/mnt/projects">/mnt/projects</DropdownItem>
                                                <DropdownItem name="/data/cg">/data/cg</DropdownItem>
                                                <DropdownItem name="/home">/home</DropdownItem>
                                                <DropdownItem name="/opt">/opt</DropdownItem>
                                            </DropdownMenu>
                                        </template>
                                    </Dropdown>
                                </template>
                            </Input>
                            <div style="color: #999; font-size: 12px; margin-top: 5px;">
                                Windows: H:\ProjectA | Linux: /mnt/projects/ProjectA
                                <br>
                                提示：请确保路径已存在且有读写权限
                                </div>
                                <div style="margin-top:8px;">
                                <Button size="small" @click="openMountModal">检测挂载/挂载共享</Button>
                            </div>
                        </FormItem>

                        <FormItem label="存储类型">
                            <RadioGroup v-model="settingsModal.form.storage_type">
                                <Radio label="local">本地存储</Radio>
                                <Radio label="network">网络存储</Radio>
                                <Radio label="cloud">云存储</Radio>
                            </RadioGroup>
                        </FormItem>

                        <FormItem label="项目代码">
                            <Input v-model="settingsModal.form.project_code" placeholder="用于文件命名，如：PRJA" />
                        </FormItem>
                    </Form>
                </TabPane>

                <!-- 路径模板 -->
                <TabPane label="路径模板" name="templates">
                    <Form :model="settingsModal.form" :label-width="120">
                        <FormItem label="资产路径模板">
                            <Select v-model="settingsModal.form.asset_template_id" clearable>
                                <Option v-for="t in pathTemplates.asset" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                    <span v-if="t.is_system" style="color: #999; font-size: 12px;"> (系统预设)</span>
                                </Option>
                            </Select>
                            <div style="color: #999; font-size: 12px; margin-top: 5px;">
                                选择资产的目录结构模板
                            </div>
                        </FormItem>

                        <FormItem label="镜头路径模板">
                            <Select v-model="settingsModal.form.shot_template_id" clearable>
                                <Option v-for="t in pathTemplates.shot" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                    <span v-if="t.is_system" style="color: #999; font-size: 12px;"> (系统预设)</span>
                                </Option>
                            </Select>
                            <div style="color: #999; font-size: 12px; margin-top: 5px;">
                                选择镜头的目录结构模板
                            </div>
                        </FormItem>

                        <FormItem label=" " :label-width="120">
                            <Button @click="showTemplateManager">管理路径模板</Button>
                        </FormItem>
                    </Form>
                </TabPane>

                <!-- 流程步骤 -->
                <TabPane label="流程步骤" name="pipeline">
                    <div class="pipeline-steps">
                        <div class="step-section">
                            <h4>资产流程</h4>
                            <Button size="small" @click="initPipeline('asset')">初始化默认步骤</Button>
                            <div class="step-list">
                                <div v-for="step in pipelineSteps.asset" :key="step.id" class="step-item">
                                    <Tag :color="step.color || 'default'">{{ step.name }}</Tag>
                                    <span class="step-code">{{ step.code }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="step-section">
                            <h4>镜头流程</h4>
                            <Button size="small" @click="initPipeline('shot')">初始化默认步骤</Button>
                            <div class="step-list">
                                <div v-for="step in pipelineSteps.shot" :key="step.id" class="step-item">
                                    <Tag :color="step.color || 'default'">{{ step.name }}</Tag>
                                    <span class="step-code">{{ step.code }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPane>
            </Tabs>

            <div slot="footer">
                <Button @click="settingsModal.visible = false">取消</Button>
                <Button type="primary" @click="saveProjectSettings" :loading="settingsModal.loading">保存</Button>
            </div>
        </Modal>

        <Modal v-model="mountModal.visible" title="挂载共享目录" width="640">
 <Form :model="mountModal.form" :label-width="110">
 <FormItem label="目标路径">
 <Input v-model="mountModal.form.path" placeholder="例如：H:\\ProjectA" />
 </FormItem>
 <FormItem>
 <Button @click="checkMountStatus" :loading="mountModal.checking">检测挂载状态</Button>
 </FormItem>

 <Alert v-if="mountModal.status" :type="isMountReady ? 'success' : 'warning'" show-icon>
 {{ isMountReady ? '已挂载且可写，可直接使用。' : '未挂载或不可写，请填写共享信息执行挂载。' }}
 </Alert>

 <template v-if="mountModal.status && !isMountReady">
 <FormItem label="盘符">
 <Input v-model="mountModal.form.drive_letter" maxlength="1" placeholder="例如：H" />
 </FormItem>
 <FormItem label="主机">
 <Input v-model="mountModal.form.host" placeholder="例如：192.168.1.10" />
 </FormItem>
 <FormItem label="共享名">
 <Input v-model="mountModal.form.share" placeholder="例如：projects" />
 </FormItem>
 <FormItem label="用户名">
 <Input v-model="mountModal.form.username" />
 </FormItem>
 <FormItem label="密码">
 <Input v-model="mountModal.form.password" type="password" password />
 </FormItem>
 </template>
 </Form>

 <div slot="footer">
 <Button @click="mountModal.visible = false">关闭</Button>
 <Button
 type="primary"
 :disabled="!mountModal.status || isMountReady"
 :loading="mountModal.loading"
 @click="submitMountShare"
 >执行挂载</Button>
 </div>
 </Modal>

 <!-- 路径模板管理弹窗 -->
        <Modal v-model="templateModal.visible" title="路径模板管理" width="900" :footer-hide="true">
            <div class="template-manager">
                <div class="template-list">
                    <div class="list-header">
                        <h4>模板列表</h4>
                        <Button size="small" type="primary" @click="showCreateTemplate">创建模板</Button>
                    </div>
                    <div class="template-items">
                        <div
                            v-for="t in allTemplates"
                            :key="t.id"
                            class="template-item"
                            :class="{ active: templateModal.selectedId === t.id }"
                            @click="selectTemplate(t)"
                        >
                            <div class="template-info">
                                <div class="name">{{ t.name }}</div>
                                <Tag v-if="t.is_system" size="small">系统预设</Tag>
                                <Tag v-else size="small" color="blue">自定义</Tag>
                            </div>
                            <div class="template-actions">
                                <Button v-if="!t.is_system" size="small" @click.stop="editTemplate(t)">编辑</Button>
                                <Button v-if="!t.is_system" size="small" type="error" @click.stop="deleteTemplate(t)">删除</Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="template-preview" v-if="templateModal.selectedTemplate">
                    <h4>模板详情</h4>
                    <div class="preview-content">
                        <div class="info-item">
                            <label>模板名称：</label>
                            <span>{{ templateModal.selectedTemplate.name }}</span>
                        </div>
                        <div class="info-item">
                            <label>模板类型：</label>
                            <span>{{ templateModal.selectedTemplate.type === 'asset' ? '资产' : '镜头' }}</span>
                        </div>
                        <div class="info-item">
                            <label>路径结构：</label>
                            <pre>{{ formatStructure(templateModal.selectedTemplate.structure) }}</pre>
                        </div>
                        <div class="info-item" v-if="templateModal.selectedTemplate.naming_rules">
                            <label>命名规则：</label>
                            <pre>{{ JSON.stringify(templateModal.selectedTemplate.naming_rules, null, 2) }}</pre>
                        </div>
                        <div class="info-item" v-if="templateModal.selectedTemplate.description">
                            <label>描述：</label>
                            <p>{{ templateModal.selectedTemplate.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script>
export default {
    data() {
        return {
            filters: {
                project_id: '',
                category_id: '',
                keyword: '',
            },
            assets: [],
            projects: [],
            categories: [],
            pagination: {
                total: 0,
                current: 1,
                per_page: 20,
            },
            createModal: {
                visible: false,
                loading: false,
                form: {
                    project_id: '',
                    category_id: '',
                    name: '',
                    code: '',
                    description: '',
                    create_structure: true,
                },
            },
            settingsModal: {
                visible: false,
                loading: false,
                activeTab: 'storage',
                form: {
                    storage_root: '',
                    storage_type: 'local',
                    project_code: '',
                    asset_template_id: null,
                    shot_template_id: null,
                },
            },
            mountModal: {
 visible: false,
 loading: false,
 checking: false,
 status: null,
 form: {
 path: '',
 drive_letter: '',
 host: '',
 share: '',
 username: '',
 password: '',
 },
 },
 pathTemplates: {
                asset: [],
                shot: [],
            },
            pipelineSteps: {
                asset: [],
                shot: [],
            },
            templateModal: {
                visible: false,
                selectedId: null,
                selectedTemplate: null,
            },
            allTemplates: [],
        }
    },

    mounted() {
        this.init()
    },

    computed: {
        isElectron() {
            return window.$A && window.$A.isElectron
        },
    },

    methods: {
        async init() {
            await this.loadProjects()
            await this.loadCategories()
            await this.loadPipelineSteps()
            await this.loadAssets()
        },
        async loadProjects() {
            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/projects',
                })

                if (res.data) {
                    this.projects = res.data

                    // 优先使用当前项目ID（从路由或store获取）
                    const currentProjectId = this.$store.state.projectId

                    if (currentProjectId && this.projects.find(p => p.id === currentProjectId)) {
                        this.filters.project_id = currentProjectId
                    } else if (this.projects.length > 0 && !this.filters.project_id) {
                        this.filters.project_id = this.projects[0].id
                    }
                } else {
                    console.error('loadProjects failed:', res)
                    this.$Message.error(res.msg || '加载项目列表失败')
                }
            } catch (error) {
                console.error('loadProjects error:', error)
                this.$Message.error('加载项目列表失败')
            }
        },

        async loadCategories() {
            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/categories',
                })

                if (res.data) {
                    this.categories = res.data
                } else {
                    console.error('loadCategories failed:', res)
                }
            } catch (error) {
                console.error('loadCategories error:', error)
            }
        },

        async loadAssets() {
            try {
                const params = {
                    ...this.filters,
                    page: this.pagination.current,
                    per_page: this.pagination.per_page,
                }

                const res = await this.$store.dispatch('call', {
                    url: 'assets/lists',
                    data: params,
                })

                if (res.data) {
                    this.assets = res.data.data
                    this.pagination.total = res.data.total
                    this.pagination.current = res.data.current_page
                } else {
                    console.error('loadAssets failed:', res)
                }
            } catch (error) {
                console.error('loadAssets error:', error)
            }
        },

        handlePageChange(page) {
            this.pagination.current = page
            this.loadAssets()
        },

        async handleProjectChange() {
            this.pagination.current = 1
            await this.loadPipelineSteps()
            await this.loadAssets()
        },

        showCreateModal() {
            if (this.projects.length === 0) {
                this.$Modal.warning({
                    title: '提示',
                    content: '您还没有项目，请先在项目管理中创建项目',
                })
                return
            }

            this.createModal.form = {
                project_id: this.filters.project_id || this.projects[0].id,
                category_id: null,
                name: '',
                code: '',
                description: '',
                create_structure: true,
            }
            this.createModal.visible = true
        },

        async handleCreateAsset() {
            if (!this.createModal.form.project_id) {
                this.$Message.warning('请选择项目')
                return
            }

            if (!this.createModal.form.category_id) {
                this.$Message.warning('请选择分类')
                return
            }

            if (!this.createModal.form.name) {
                this.$Message.warning('请输入资产名称')
                return
            }

            if (!this.createModal.form.code) {
                this.$Message.warning('请输入资产编码')
                return
            }

            this.createModal.loading = true

            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/store',
                    method: 'post',
                    data: this.createModal.form,
                })

                this.createModal.loading = false

                if (res.data) {
                    this.$Message.success('资产创建成功')
                    this.createModal.visible = false
                    this.loadAssets()
                } else {
                    this.$Message.error(res.msg || '创建失败')
                }
            } catch (error) {
 this.createModal.loading = false
 const mountInfo = error && error.data && (error.data.type === 'mount_required' || error.data.type === 'mount_not_writable') ? error.data : null
 if (mountInfo) {
 this.$Message.error(error.msg || '目录盘符未挂载，请先挂载')
 this.mountModal.visible = true
 this.mountModal.status = {
 mounted: false,
 drive_letter: mountInfo.drive_letter,
 mount_point: mountInfo.mount_point,
 }
 this.mountModal.form.path = mountInfo.input_path || ''
 this.mountModal.form.drive_letter = mountInfo.drive_letter || ''
 return
 }
 this.$Message.error(error.msg || '创建失败')
 }
 },

        openAssetDetail(asset) {
            this.$router.push({
                name: 'manage-assets-detail',
                params: { id: asset.id },
            })
        },

        getStatusText(status) {
            const map = {
                draft: '草稿',
                review: '审核中',
                approved: '已批准',
                archived: '已归档',
            }
            return map[status] || status
        },

        async openAssetStepFolder(asset, step) {
            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/localPath',
                    data: { id: asset.id },
                })

                if (!res.data || !res.data.absolute_path) {
                    this.$Message.error(res.msg || '获取资产路径失败')
                    return
                }

                const stepPath = `${res.data.absolute_path}/${step.name}`

                if (this.isElectron && $A.Electron) {
                    $A.Electron.request({
                        action: 'openPath',
                        path: stepPath,
                    }, (ret) => {
                        if (ret) {
                            this.$Message.error(ret)
                        }
                    }, () => {
                        this.$Message.error('打开目录失败')
                    })
                    return
                }

                this.copyText({
                    text: stepPath,
                    success: '目录路径已复制',
                    error: '目录路径复制失败',
                })
                this.$Modal.info({
                    title: 'Web 环境不支持直接打开目录',
                    content: `请在本地文件管理器打开：${stepPath}`,
                })
            } catch (error) {
                this.$Message.error(error.msg || '打开目录失败')
            }
        },

        async showProjectSettings() {
            if (!this.filters.project_id) {
                this.$Message.warning('请先选择项目')
                return
            }

            // 加载路径模板
            await this.loadPathTemplates()

            // 加载流程步骤
            await this.loadPipelineSteps()

            // 加载当前项目配置
            const project = this.projects.find(p => p.id === this.filters.project_id)
            if (project) {
                this.settingsModal.form = {
                    storage_root: project.storage_root || '',
                    storage_type: project.storage_type || 'local',
                    project_code: project.project_code || '',
                    asset_template_id: project.asset_template_id || null,
                    shot_template_id: project.shot_template_id || null,
                }
            }

            this.settingsModal.visible = true
        },

        async loadPathTemplates() {
            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/pathTemplates',
                })

                if (res.data) {
                    this.pathTemplates.asset = res.data.filter(t => t.type === 'asset')
                    this.pathTemplates.shot = res.data.filter(t => t.type === 'shot')
                }
            } catch (error) {
                console.error('loadPathTemplates error:', error)
            }
        },

        async loadPipelineSteps() {
            try {
                if (!this.filters.project_id) {
                    this.pipelineSteps.asset = []
                    this.pipelineSteps.shot = []
                    return
                }

                const res = await this.$store.dispatch('call', {
                    url: 'assets/pipelineSteps',
                    data: { project_id: this.filters.project_id },
                })

                if (res.data) {
                    this.pipelineSteps.asset = res.data.filter(s => s.type === 'asset' || s.type === 'both')
                    this.pipelineSteps.shot = res.data.filter(s => s.type === 'shot' || s.type === 'both')
                }
            } catch (error) {
                console.error('loadPipelineSteps error:', error)
            }
        },

        async initPipeline(type) {
            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/initProjectPipeline',
                    data: {
                        project_id: this.filters.project_id,
                        preset: type,
                    },
                })

                if (res.ret === 1) {
                    this.$Message.success('初始化成功')
                    await this.loadPipelineSteps()
                } else {
                    this.$Message.error(res.msg || '初始化失败')
                }
            } catch (error) {
                this.$Message.error(error.msg || '初始化失败')
            }
        },

        async saveProjectSettings() {
            if (!this.filters.project_id) {
                this.$Message.warning('请先选择项目')
                return
            }

            this.settingsModal.loading = true

            try {
                const res = await this.$store.dispatch('call', {
                    url: 'project/update',
                    data: {
                        project_id: this.filters.project_id,
                        ...this.settingsModal.form,
                    },
                })

                if (res.ret === 1) {
                    this.$Message.success('保存成功')
                    // 重新加载项目列表以获取最新数据
                    await this.loadProjects()
                    this.settingsModal.visible = false
                    this.settingsModal.loading = false
                } else {
                    this.settingsModal.loading = false
                    this.$Message.error(res.msg || '保存失败')
                }
            } catch (error) {
                this.settingsModal.loading = false
                this.$Message.error(error.msg || '保存失败')
            }
        },

        async handleQuickPath(path) {
            // 如果在 Electron 环境下，使用原生文件夹选择对话框
            if (window.$A && window.$A.isElectron) {
                try {
                    const result = await window.$A.electronShowOpenDialog({
                        properties: ['openDirectory'],
                        title: '选择存储根路径',
                    })
                    if (result && result.filePaths && result.filePaths.length > 0) {
                        this.settingsModal.form.storage_root = result.filePaths[0]
                    }
                } catch (error) {
                    this.$Message.error('打开文件夹选择对话框失败')
                }
            } else {
                // Web 端使用快捷路径
                this.settingsModal.form.storage_root = path
            }
        },

     
 openMountModal() {
 this.mountModal.visible = true
 this.mountModal.status = null
 this.mountModal.form.path = this.settingsModal.form.storage_root || ''
 this.mountModal.form.drive_letter = ''
 this.mountModal.form.host = ''
 this.mountModal.form.share = ''
 this.mountModal.form.username = ''
 this.mountModal.form.password = ''
 },

 async checkMountStatus() {
 if (!this.mountModal.form.path) {
 this.$Message.warning('请先输入目标路径')
 return
 }

 this.mountModal.checking = true
 try {
 const res = await this.$store.dispatch('call', {
 url: 'assets/mountStatus',
 method: 'post',
 data: { path: this.mountModal.form.path },
 })

 this.mountModal.checking = false

 if (res.data) {
 this.mountModal.status = res.data
 if (res.data.drive_letter) {
 this.mountModal.form.drive_letter = res.data.drive_letter
 }
 if (res.data.mounted && res.data.writable) {
 this.$Message.success('挂载已就绪（可写）')
 } else {
 this.$Message.warning('路径未就绪，请执行挂载')
 }
 } else {
 this.$Message.error(res.msg || '检测失败')
 }
 } catch (error) {
 this.mountModal.checking = false
 this.$Message.error(error.msg || '检测失败')
 }
 },

 async submitMountShare() {
 const form = this.mountModal.form
 if (!form.drive_letter || !form.host || !form.share || !form.username || !form.password) {
 this.$Message.warning('请完整填写挂载信息')
 return
 }

 this.mountModal.loading = true
 try {
 const res = await this.$store.dispatch('call', {
 url: 'assets/mountShare',
 method: 'post',
 data: {
 drive_letter: form.drive_letter,
 host: form.host,
 share: form.share,
 username: form.username,
 password: form.password,
 },
 })

 this.mountModal.loading = false

 if (res.data) {
 this.mountModal.status = {
 ...res.data,
 }
 if (res.data.mounted && res.data.writable) {
 this.$Message.success('挂载成功')
 } else {
 this.$Message.warning('挂载后路径仍不可写，请检查共享权限')
 }
 } else {
 this.$Message.error(res.msg || '挂载失败')
 }
 } catch (error) {
 this.mountModal.loading = false
 this.$Message.error(error.msg || (error.data && error.data.message) || '挂载失败')
 console.error('mountShare error detail:', error)
 }
 },

 async showTemplateManager() {
            await this.loadAllTemplates()
            this.templateModal.visible = true
        },

        async loadAllTemplates() {
            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/pathTemplates',
                })

                if (res.data) {
                    this.allTemplates = res.data
                }
            } catch (error) {
                console.error('loadAllTemplates error:', error)
            }
        },

        selectTemplate(template) {
            this.templateModal.selectedId = template.id
            this.templateModal.selectedTemplate = template
        },

        formatStructure(structure) {
            if (!structure || !Array.isArray(structure)) return ''

            return structure.map((level, index) => {
                const indent = '  '.repeat(level.level - 1)
                const name = level.fixed ? level.name : `{${level.source || level.name}}`
                return `${indent}${name}/`
            }).join('\n')
        },

        showCreateTemplate() {
            this.$Message.info('模板创建功能开发中，当前可使用系统预设模板')
        },

        editTemplate(template) {
            this.$Message.info('模板编辑功能开发中')
        },

        deleteTemplate(template) {
            this.$Modal.confirm({
                title: '确认删除',
                content: `确定要删除模板"${template.name}"吗？`,
                onOk: async () => {
                    try {
                        const res = await this.$store.dispatch('call', {
                            url: `assets/path-templates/${template.id}`,
                            method: 'delete',
                        })

                        if (res.data) {
                            this.$Message.success('删除成功')
                            await this.loadAllTemplates()
                        } else {
                            this.$Message.error(res.msg || '删除失败')
                        }
                    } catch (error) {
                        this.$Message.error(error.msg || '删除失败')
                    }
                },
            })
        },
    },
}
</script>

<style lang="scss" scoped>
.asset-library-page {
    padding: 20px;
    background: #f5f5f5;
    min-height: calc(100vh - 60px);
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px 20px;
    background: white;
    border-radius: 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);

    .left-tools {
        display: flex;
        gap: 10px;
    }

    .right-tools {
        display: flex;
        gap: 10px;
    }
}

.asset-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.asset-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }

    .thumbnail {
        position: relative;
        width: 100%;
        height: 160px;
        background: #f0f0f0;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .status-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: white;

            &.status-draft {
                background: #999;
            }

            &.status-review {
                background: #ff9900;
            }

            &.status-approved {
                background: #19be6b;
            }

            &.status-archived {
                background: #ed4014;
            }
        }
    }

    .info {
        padding: 12px;

        .name-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;

            .name {
                font-size: 16px;
                font-weight: 500;
                min-width: 0;
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .type-buttons {
                display: flex;
                align-items: center;
                gap: 4px;
                flex-wrap: wrap;
            }
        }

        .code {
            font-size: 12px;
            color: #999;
            margin-bottom: 8px;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;

            .creator {
                color: #666;
            }
        }
    }
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 8px;

    p {
        margin: 20px 0;
        color: #999;
        font-size: 16px;
    }
}

.pipeline-steps {
    .step-section {
        margin-bottom: 30px;

        h4 {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .step-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;

            .step-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                background: #f5f5f5;
                border-radius: 4px;

                .step-code {
                    font-size: 12px;
                    color: #999;
                }
            }
        }
    }
}

.template-manager {
    display: flex;
    gap: 20px;
    min-height: 400px;

    .template-list {
        flex: 1;
        border-right: 1px solid #e8e8e8;
        padding-right: 20px;

        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;

            h4 {
                margin: 0;
            }
        }

        .template-items {
            .template-item {
                padding: 12px;
                border: 1px solid #e8e8e8;
                border-radius: 4px;
                margin-bottom: 10px;
                cursor: pointer;
                transition: all 0.3s;

                &:hover {
                    border-color: #2d8cf0;
                    background: #f0f7ff;
                }

                &.active {
                    border-color: #2d8cf0;
                    background: #e6f2ff;
                }

                .template-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 8px;

                    .name {
                        font-weight: 500;
                        flex: 1;
                    }
                }

                .template-actions {
                    display: flex;
                    gap: 8px;
                }
            }
        }
    }

    .template-preview {
        flex: 1.5;

        h4 {
            margin-bottom: 15px;
        }

        .preview-content {
            .info-item {
                margin-bottom: 15px;

                label {
                    font-weight: 500;
                    color: #666;
                    display: block;
                    margin-bottom: 5px;
                }

                pre {
                    background: #f5f5f5;
                    padding: 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    line-height: 1.6;
                    overflow-x: auto;
                }

                p {
                    color: #666;
                    line-height: 1.6;
                }
            }
        }
    }
}
</style>
