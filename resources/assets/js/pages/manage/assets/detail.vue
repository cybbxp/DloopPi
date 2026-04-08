<template>
    <div class="asset-detail-page">
        <div class="detail-header">
            <Button icon="ios-arrow-back" @click="$router.back()">返回</Button>
            <h2>{{ asset.name }}</h2>
            <div class="actions">
                <Button icon="md-folder-open" @click="showLocalPath">本地路径</Button>
                <Button icon="md-cloud-upload" type="primary" @click="showUploadModal">上传版本</Button>
            </div>
        </div>

        <div class="detail-content">
            <!-- 左侧：资产信息 -->
            <div class="asset-info">
                <div class="info-card">
                    <div class="thumbnail">
                        <img :src="asset.thumbnail_url || '/images/default-asset.png'" />
                    </div>

                    <div class="info-item">
                        <label>资产编码：</label>
                        <span>{{ asset.code }}</span>
                    </div>

                    <div class="info-item">
                        <label>分类：</label>
                        <Tag>{{ asset.category ? asset.category.name : '' }}</Tag>
                    </div>

                    <div class="info-item">
                        <label>状态：</label>
                        <Tag :color="getStatusColor(asset.status)">{{ getStatusText(asset.status) }}</Tag>
                    </div>

                    <div class="info-item">
                        <label>项目：</label>
                        <span>{{ asset.project ? asset.project.name : '' }}</span>
                    </div>

                    <div class="info-item">
                        <label>相对路径：</label>
                        <div class="path-display">
                            <code>{{ asset.full_path || asset.storage_path }}</code>
                            <Button size="small" icon="md-copy" @click="copyPath(asset.full_path)">复制</Button>
                        </div>
                    </div>

                    <div class="info-item" v-if="asset.full_path">
                        <label>完整路径：</label>
                        <div class="path-display">
                            <code>{{ getFullPathDisplay() }}</code>
                            <Button size="small" icon="md-copy" @click="copyPath(getFullPathDisplay())">复制</Button>
                        </div>
                    </div>

                    <div class="info-item">
                        <label>创建人：</label>
                        <span>{{ asset.creator ? asset.creator.nickname : '' }}</span>
                    </div>

                    <div class="info-item">
                        <label>创建时间：</label>
                        <span>{{ formatDate(asset.created_at) }}</span>
                    </div>

                    <div class="info-item" v-if="asset.description">
                        <label>描述：</label>
                        <p>{{ asset.description }}</p>
                    </div>

                    <div class="info-item" v-if="asset.tags && asset.tags.length > 0">
                        <label>标签：</label>
                        <Tag v-for="tag in asset.tags" :key="tag">{{ tag }}</Tag>
                    </div>
                </div>
            </div>

            <!-- 右侧：版本列表（按流程步骤分组） -->
            <div class="version-list">
                <Tabs v-model="activeStep" @on-click="loadVersionsByStep">
                    <TabPane
                        v-for="step in pipelineSteps"
                        :key="step.code"
                        :label="step.name"
                        :name="step.code"
                    >
                        <div v-if="stepVersions[step.code] && stepVersions[step.code].length > 0" class="versions">
                            <!-- 当前版本 -->
                            <div
                                v-for="version in stepVersions[step.code].filter(v => v.is_current)"
                                :key="version.id"
                                class="version-item current-version"
                            >
                                <div class="version-header">
                                    <div class="version-number">
                                        <Tag color="success">当前版本</Tag>
                                        v{{ String(version.version).padStart(3, '0') }}
                                    </div>
                                    <div class="version-meta">
                                        <span class="creator">{{ version.creator ? version.creator.nickname : '' }}</span>
                                        <span class="time">{{ formatDate(version.created_at) }}</span>
                                    </div>
                                </div>

                                <div class="version-body">
                                    <div class="file-info">
                                        <Icon type="md-document" size="20" />
                                        <span class="file-name">{{ version.file_name }}</span>
                                        <span class="file-size">{{ formatFileSize(version.file_size) }}</span>
                                    </div>

                                    <div v-if="version.file_path_current" class="file-path">
                                        <Icon type="md-folder" />
                                        <code>{{ version.file_path_current }}</code>
                                        <Button size="small" icon="md-copy" @click="copyPath(version.file_path_current)">复制</Button>
                                    </div>

                                    <div v-if="version.comment" class="comment">
                                        <Icon type="md-chatbubbles" />
                                        {{ version.comment }}
                                    </div>

                                    <div class="version-actions">
                                        <Button size="small" icon="md-download" @click="downloadVersion(version)">下载</Button>
                                        <Button size="small" icon="md-open" @click="openInExplorer(version)">打开文件夹</Button>
                                    </div>
                                </div>
                            </div>

                            <!-- 历史版本 -->
                            <Collapse v-if="stepVersions[step.code].filter(v => !v.is_current).length > 0">
                                <Panel name="history">
                                    历史版本 ({{ stepVersions[step.code].filter(v => !v.is_current).length }})
                                    <div slot="content">
                                        <div
                                            v-for="version in stepVersions[step.code].filter(v => !v.is_current)"
                                            :key="version.id"
                                            class="version-item history-version"
                                        >
                                            <div class="version-header">
                                                <div class="version-number">v{{ String(version.version).padStart(3, '0') }}</div>
                                                <div class="version-meta">
                                                    <span class="creator">{{ version.creator ? version.creator.nickname : '' }}</span>
                                                    <span class="time">{{ formatDate(version.created_at) }}</span>
                                                </div>
                                            </div>

                                            <div class="version-body">
                                                <div class="file-info">
                                                    <Icon type="md-document" size="16" />
                                                    <span class="file-name">{{ version.file_name }}</span>
                                                    <span class="file-size">{{ formatFileSize(version.file_size) }}</span>
                                                </div>

                                                <div v-if="version.comment" class="comment">
                                                    {{ version.comment }}
                                                </div>

                                                <div class="version-actions">
                                                    <Button size="small" icon="md-download" @click="downloadVersion(version)">下载</Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Panel>
                            </Collapse>
                        </div>

                        <div v-else class="empty-versions">
                            <p>该流程暂无版本</p>
                            <Button type="primary" @click="showUploadModal">上传第一个版本</Button>
                        </div>
                    </TabPane>
                </Tabs>
            </div>
        </div>

        <!-- 上传版本弹窗 -->
        <Modal v-model="uploadModal.visible" title="上传新版本" width="500">
            <Form :model="uploadModal.form" :label-width="80">
                <FormItem label="流程步骤">
                    <Select v-model="uploadModal.form.step_code">
                        <Option
                            v-for="step in pipelineSteps"
                            :key="step.code"
                            :value="step.code"
                        >
                            {{ step.name }}
                        </Option>
                    </Select>
                </FormItem>

                <FormItem label="选择文件">
                    <Upload
                        action=""
                        :before-upload="beforeUpload"
                        :show-upload-list="false"
                        accept="*"
                    >
                        <Button icon="ios-cloud-upload-outline">选择文件</Button>
                    </Upload>
                    <div v-if="uploadModal.file" class="upload-file-info">
                        已选择：{{ uploadModal.file.name }}
                    </div>
                </FormItem>

                <FormItem label="版本说明">
                    <Input
                        v-model="uploadModal.form.comment"
                        type="textarea"
                        :rows="3"
                        placeholder="描述本次更新的内容..."
                    />
                </FormItem>
            </Form>

            <div slot="footer">
                <Button @click="uploadModal.visible = false">取消</Button>
                <Button type="primary" @click="handleUploadVersion" :loading="uploadModal.loading">上传</Button>
            </div>
        </Modal>

        <!-- 本地路径弹窗 -->
        <Modal v-model="localPathModal.visible" title="本地路径" width="600">
            <div class="local-path-info">
                <div class="path-item">
                    <label>存储根路径：</label>
                    <div class="path-value">
                        <code>{{ localPathInfo.storage_root || '未配置' }}</code>
                        <Button size="small" icon="md-copy" @click="copyPath(localPathInfo.storage_root)">复制</Button>
                    </div>
                </div>

                <div class="path-item">
                    <label>相对路径：</label>
                    <div class="path-value">
                        <code>{{ localPathInfo.relative_path }}</code>
                        <Button size="small" icon="md-copy" @click="copyPath(localPathInfo.relative_path)">复制</Button>
                    </div>
                </div>

                <div class="path-item">
                    <label>绝对路径：</label>
                    <div class="path-value">
                        <code>{{ localPathInfo.absolute_path }}</code>
                        <Button size="small" icon="md-copy" @click="copyPath(localPathInfo.absolute_path)">复制</Button>
                    </div>
                </div>

                <div class="path-item">
                    <label>存储类型：</label>
                    <Tag>{{ localPathInfo.storage_type }}</Tag>
                </div>

                <Alert type="info" show-icon>
                    提示：可以直接在本地文件管理器中打开此路径进行文件操作
                </Alert>
            </div>

            <div slot="footer">
                <Button @click="localPathModal.visible = false">关闭</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
export default {
    data() {
        return {
            asset: {},
            versions: [],
            pipelineSteps: [],
            stepVersions: {},
            activeStep: '',
 currentAssetId:0,

            uploadModal: {
                visible: false,
                loading: false,
                file: null,
                form: {
                    step_code: '',
                    comment: '',
                },
            },

            localPathModal: {
                visible: false,
            },

            localPathInfo: {
                storage_root: '',
                relative_path: '',
                absolute_path: '',
                storage_type: '',
            },
        }
    },

    async mounted() {
 await this.reloadByRoute(this.$route.params.id)
 },

 watch: {
 '$route.params.id': {
 async handler(newId, oldId) {
 if (newId && newId !== oldId) {
 await this.reloadByRoute(newId)
 }
 },
 },
 },

 methods: {

        async reloadByRoute(routeId = null) {
 const targetId = Number(routeId || this.$route.params.id ||0)
 if (!targetId) {
 return
 }

 this.currentAssetId = targetId
 this.asset = {}
 this.versions = []
 this.stepVersions = {}
 this.localPathInfo = {
 storage_root: '',
 relative_path: '',
 absolute_path: '',
 storage_type: '',
 }

 await this.loadAssetDetail(targetId)

 if (this.currentAssetId !== targetId) {
 return
 }

 await this.loadPipelineSteps(this.asset.project_id || this.$store.state.projectId)
 },

 async loadAssetDetail(assetId = null) {
            const targetId = Number(assetId || this.$route.params.id ||0)
 if (!targetId) {
 return
 }

 const res = await this.$store.dispatch('call', {
                url: 'assets/detail',
                data: {
                    id: targetId,
                },
            })

            if (this.currentAssetId !== targetId) {
 return
 }

 if (res.data) {
                this.asset = res.data
                this.versions = res.data.versions || []
                this.groupVersionsByStep()

                // 加载完整路径信息
                await this.loadFullPathInfo(targetId)
            }
        },

        async loadFullPathInfo(assetId = null) {
            try {
                const res = await this.$store.dispatch('call', {
                    url: 'assets/localPath',
                    data: {
                        id: Number(assetId || this.currentAssetId || this.$route.params.id ||0),
                    },
                })

                if (res.data) {
                    this.localPathInfo = res.data
                }
            } catch (error) {
                console.error('加载完整路径信息失败:', error)
            }
        },

 async loadPipelineSteps(projectId = null) {
 const resolvedProjectId = projectId || this.asset.project_id || this.$store.state.projectId
 if (!resolvedProjectId) {
 this.pipelineSteps = []
 this.uploadModal.form.step_code = ''
 this.activeStep = ''
 return
 }

 try {
 const res = await this.$store.dispatch('call', {
 url: 'assets/pipelineSteps',
 data: {
 project_id: resolvedProjectId,
 type: 'asset',
 },
 })

 this.pipelineSteps = res.data || []

 if (this.pipelineSteps.length >0) {
 const hasCurrentActiveStep = this.pipelineSteps.some(step => step.code === this.activeStep)
 if (!hasCurrentActiveStep) {
 this.activeStep = this.pipelineSteps[0].code
 }

 const hasCurrentUploadStep = this.pipelineSteps.some(step => step.code === this.uploadModal.form.step_code)
 if (!hasCurrentUploadStep) {
 this.uploadModal.form.step_code = this.pipelineSteps[0].code
 }
 } else {
 this.activeStep = ''
 this.uploadModal.form.step_code = ''
 }
 } catch (error) {
 console.error('loadPipelineSteps error:', error)
 this.pipelineSteps = []
 this.activeStep = ''
 this.uploadModal.form.step_code = ''
 }
 },

 groupVersionsByStep() {
            this.stepVersions = {}
            this.versions.forEach(version => {
                const stepCode = version.step_code || 'model'
                if (!this.stepVersions[stepCode]) {
                    this.stepVersions[stepCode] = []
                }
                this.stepVersions[stepCode].push(version)
            })

            // 排序：当前版本在前，历史版本按版本号倒序
            Object.keys(this.stepVersions).forEach(stepCode => {
                this.stepVersions[stepCode].sort((a, b) => {
                    if (a.is_current && !b.is_current) return -1
                    if (!a.is_current && b.is_current) return 1
                    return b.version - a.version
                })
            })
        },

        loadVersionsByStep(stepCode) {
            // 切换 Tab 时的回调，可以在这里加载更多数据
            console.log('切换到流程：', stepCode)
        },

        async showUploadModal() {
 await this.loadPipelineSteps(this.asset.project_id || this.$store.state.projectId)

 if (!this.uploadModal.form.step_code) {
 this.$Message.warning('当前项目没有可用流程步骤，已自动尝试初始化，请刷新后重试')
 return
 }

 this.uploadModal.visible = true
 this.uploadModal.file = null
 this.uploadModal.form.comment = ''
 },

 beforeUpload(file) {
            this.uploadModal.file = file
            return false
        },

        async handleUploadVersion() {
            if (!this.uploadModal.file) {
                this.$Message.warning('请选择文件')
                return
            }

            if (!this.uploadModal.form.step_code) {
                this.$Message.warning('请选择流程步骤')
                return
            }

            this.uploadModal.loading = true

            const formData = new FormData()
            formData.append('id', this.$route.params.id)
            formData.append('file', this.uploadModal.file)
            formData.append('step_code', this.uploadModal.form.step_code)
            formData.append('comment', this.uploadModal.form.comment)

            try {
                const response = await fetch(this.$A.apiUrl('assets/uploadVersion'), {
                    method: 'POST',
                    headers: {
                        'fd': this.$A.getSessionStorageString("userWsFd"),
                        'token': this.$store.state.userToken,
                    },
                    body: formData,
                })

                const result = await response.json()
                this.uploadModal.loading = false

                if (result.ret === 1) {
                    this.$Message.success('版本上传成功')
                    this.uploadModal.visible = false
                    this.loadAssetDetail()
                } else {
                    this.$Message.error(result.msg || '上传失败')
                }
            } catch (error) {
                console.error('Upload error:', error)
                this.uploadModal.loading = false
                this.$Message.error('上传失败，请重试')
            }
        },

        downloadVersion(version) {
            const url = this.$A.apiUrl('assets/downloadVersion') +
                `?id=${this.asset.id}&version=${version.version}&token=${this.$store.state.userToken}`
            window.open(url)
        },

        async showLocalPath() {
            const res = await this.$store.dispatch('call', {
                url: 'assets/localPath',
                data: {
                    id: this.currentAssetId || Number(this.$route.params.id ||0),
                },
            })

            if (res.data) {
                this.localPathInfo = res.data
                this.localPathModal.visible = true
            }
        },

        copyPath(path) {
            if (!path) {
                this.$Message.warning('路径为空')
                return
            }

            const textarea = document.createElement('textarea')
            textarea.value = path
            document.body.appendChild(textarea)
            textarea.select()
            document.execCommand('copy')
            document.body.removeChild(textarea)

            this.$Message.success('路径已复制到剪贴板')
        },

        getFullPathDisplay() {
            // 使用 localPathInfo 中的 absolute_path，如果没有则使用 getFullPath 方法
            if (this.localPathInfo && this.localPathInfo.absolute_path) {
                return this.localPathInfo.absolute_path
            }

            if (!this.asset.full_path || !this.asset.project) {
                return ''
            }

            const storageRoot = this.asset.project.storage_root || ''
            const projectFolder = (this.asset.project.name || this.asset.project.project_code || this.asset.project.id || '').toString().trim()
            const relativePath = this.asset.full_path

            if (!storageRoot) {
                return projectFolder ? `${projectFolder}/${relativePath}` : relativePath
            }

            // 处理 Windows 路径
            if (/^[A-Z]:/i.test(storageRoot)) {
                const normalizedRoot = storageRoot.replace(/[\\/]+$/, '')
                const rootWin = normalizedRoot.replace(/\//g, '\\')
                const lastSegment = rootWin.split('\\').filter(Boolean).pop() || ''
                const base = lastSegment.toLowerCase() === projectFolder.toLowerCase()
                    ? rootWin
                    : `${rootWin}\\${projectFolder}`
                return `${base}\\${relativePath.replace(/\//g, '\\')}`
            }

            // Linux 路径
            const normalizedRoot = storageRoot.replace(/\/+$/, '')
            const rootParts = normalizedRoot.split('/').filter(Boolean)
            const lastSegment = rootParts.length > 0 ? rootParts[rootParts.length - 1] : ''
            const base = lastSegment === projectFolder
                ? normalizedRoot
                : `${normalizedRoot}/${projectFolder}`
            return `${base}/${relativePath}`
        },

        openInExplorer(version) {
            this.$Message.info('请在本地文件管理器中打开：' + version.file_path_current)
        },

        formatDate(date) {
            if (!date) return ''
            return date.replace(/T/, ' ').replace(/\.\d+Z$/, '')
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 B'
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
        },

        getStatusColor(status) {
            const colors = {
                draft: 'default',
                review: 'warning',
                approved: 'success',
                archived: 'error',
            }
            return colors[status] || 'default'
        },

        getStatusText(status) {
            const texts = {
                draft: '草稿',
                review: '审核中',
                approved: '已批准',
                archived: '已归档',
            }
            return texts[status] || status
        },
    },
}
</script>

<style scoped lang="scss">
.asset-detail-page {
    padding: 20px;
}

.detail-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;

    h2 {
        flex: 1;
        margin: 0;
    }

    .actions {
        display: flex;
        gap: 10px;
    }
}

.detail-content {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 20px;
}

.asset-info {
    .info-card {
        background: #fff;
        border-radius: 4px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .thumbnail {
        width: 100%;
        height: 200px;
        background: #f5f5f5;
        border-radius: 4px;
        margin-bottom: 20px;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    .info-item {
        margin-bottom: 15px;

        label {
            display: block;
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
        }

        .path-display {
            display: flex;
            align-items: center;
            gap: 10px;

            code {
                flex: 1;
                padding: 5px 10px;
                background: #f5f5f5;
                border-radius: 3px;
                font-size: 12px;
                word-break: break-all;
            }
        }
    }
}

.version-list {
    background: #fff;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.versions {
    .version-item {
        border: 1px solid #e8e8e8;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;

        &.current-version {
            border-color: #19be6b;
            background: #f6ffed;
        }

        &.history-version {
            background: #fafafa;
        }
    }

    .version-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;

        .version-number {
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .version-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #999;
        }
    }

    .version-body {
        .file-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;

            .file-name {
                flex: 1;
                font-weight: 500;
            }

            .file-size {
                color: #999;
                font-size: 12px;
            }
        }

        .file-path {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 3px;

            code {
                flex: 1;
                font-size: 12px;
                word-break: break-all;
            }
        }

        .comment {
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #2d8cf0;
            margin-bottom: 10px;
            font-size: 13px;
            color: #666;
        }

        .version-actions {
            display: flex;
            gap: 10px;
        }
    }
}

.empty-versions {
    text-align: center;
    padding: 40px;
    color: #999;

    p {
        margin-bottom: 15px;
    }
}

.upload-file-info {
    margin-top: 10px;
    padding: 8px;
    background: #f5f5f5;
    border-radius: 3px;
    font-size: 13px;
}

.local-path-info {
    .path-item {
        margin-bottom: 20px;

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .path-value {
            display: flex;
            align-items: center;
            gap: 10px;

            code {
                flex: 1;
                padding: 10px;
                background: #f5f5f5;
                border-radius: 3px;
                word-break: break-all;
            }
        }
    }
}
</style>
