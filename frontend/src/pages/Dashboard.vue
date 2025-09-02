<template>
  <q-page class="q-pa-sm q-pa-md-md">


    <!-- 统计卡片 -->
    <div class="stats-grid q-mb-lg">
      <q-card
        class="stats-card stats-card--clickable"
        :class="{ 'stats-card--active': statusFilter === 'all' }"
        @click="filterByStatus('all')"
      >
        <q-card-section class="stats-card__content">
          <div class="stats-card__icon">
            <q-icon name="devices" size="24px" />
          </div>
          <div class="stats-card__number" :class="statusFilter === 'all' ? '' : 'text-primary'">{{ deviceStats.total }}</div>
          <div class="stats-card__label" :class="statusFilter === 'all' ? 'text-grey-3' : 'text-grey-6'">总设备数</div>
        </q-card-section>
      </q-card>

      <q-card
        class="stats-card stats-card--clickable"
        :class="{ 'stats-card--active stats-card--online': statusFilter === 'online' }"
        @click="filterByStatus('online')"
      >
        <q-card-section class="stats-card__content">
          <div class="stats-card__icon">
            <q-icon name="wifi" size="24px" />
          </div>
          <div class="stats-card__number" :class="statusFilter === 'online' ? 'text-white' : 'text-positive'">{{ deviceStats.online }}</div>
          <div class="stats-card__label" :class="statusFilter === 'online' ? 'text-grey-3' : 'text-grey-6'">在线设备</div>
        </q-card-section>
      </q-card>

      <q-card
        class="stats-card stats-card--clickable"
        :class="{ 'stats-card--active stats-card--offline': statusFilter === 'offline' }"
        @click="filterByStatus('offline')"
      >
        <q-card-section class="stats-card__content">
          <div class="stats-card__icon">
            <q-icon name="wifi_off" size="24px" />
          </div>
          <div class="stats-card__number" :class="statusFilter === 'offline' ? 'text-white' : 'text-negative'">{{ deviceStats.offline }}</div>
          <div class="stats-card__label" :class="statusFilter === 'offline' ? 'text-grey-3' : 'text-grey-6'">离线设备</div>
        </q-card-section>
      </q-card>

      <q-card class="stats-card">
        <q-card-section class="stats-card__content">
          <div class="stats-card__icon">
            <q-icon name="analytics" size="24px" />
          </div>
          <div class="stats-card__number text-info">{{ deviceStats.onlineRate }}%</div>
          <div class="stats-card__label text-grey-6">在线率</div>
        </q-card-section>
      </q-card>
    </div>

    <!-- 设备列表 -->
    <q-card>
      <q-card-section>
        <div class="row items-center q-mb-md q-col-gutter-sm">
          <div class="col-12 col-sm-6">
            <div class="text-h6">设备列表</div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="row q-gutter-sm justify-end">
              <div class="col-12 col-sm-auto">
                <q-select
                  v-model="searchType"
                  :options="searchTypeOptions"
                  option-value="value"
                  option-label="label"
                  emit-value
                  map-options
                  dense
                  outlined
                  class="full-width"
                  style="min-width: 120px"
                />
              </div>
              <div class="col-12 col-sm">
                <q-input
                  v-model="searchQuery"
                  :placeholder="searchPlaceholder"
                  dense
                  outlined
                  clearable
                  class="full-width"
                >
                  <template v-slot:prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
            </div>
          </div>
        </div>

        <!-- 设备卡片网格 -->
        <div v-if="loading" class="row justify-center q-pa-lg">
          <q-spinner-dots size="50px" color="primary" />
        </div>

        <div v-else-if="filteredDevicesCount === 0" class="text-center q-pa-lg text-grey-6">
          <q-icon name="devices_other" size="64px" class="q-mb-md" />
          <div class="text-h6">暂无设备</div>
          <div class="text-body2">
            {{ searchQuery ? '没有找到匹配的设备' : '还没有添加任何设备' }}
          </div>
        </div>

        <div v-else class="row q-col-gutter-md">
          <div
            v-for="device in deviceStore.sortedDevices"
            :key="device.device_id"
            class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
            v-show="isDeviceVisible(device)"
          >
            <q-card
              class="device-card cursor-pointer"
              :class="getDeviceStatusClass(device)"
              flat
              bordered
              style="border-radius: 12px; overflow: hidden;"
              @click="viewDevice(device)"
            >
              <!-- 设备头部信息 -->
              <q-card-section class="q-pb-sm">
                <div class="row items-center justify-between">
                  <div class="col" style="min-width: 0; overflow: hidden">
                    <q-item-label class="text-weight-medium">
                      <q-icon
                        :name="getDeviceIcon(device)"
                        :color="getDeviceStatusColor(isDeviceOnline(device.last_heartbeat) ? 'online' : 'offline')"
                        class="q-mr-xs"
                        size="16px"
                      />
                      {{ device.device_name }}
                    </q-item-label>
                    <q-item-label caption class="text-grey-6">
                      {{ device.device_id }}
                    </q-item-label>
                  </div>
                  <div class="col-auto">
                    <q-badge
                      :color="getDeviceStatusColor(isDeviceOnline(device.last_heartbeat) ? 'online' : 'offline')"
                      :label="getDeviceStatusText(device)"
                    />
                  </div>
                </div>
              </q-card-section>

              <!-- 设备基础信息 -->
              <q-card-section class="q-py-md">
                <div class="row q-gutter-y-sm">
                  <!-- 编号和IP地址 -->
                  <div class="col-12">
                    <div class="row q-gutter-x-md" style="display: flex; flex-wrap: nowrap;">
                      <div class="col" style="flex: 1; min-width: 0;">
                        <div class="row items-center q-gutter-x-xs">
                          <q-icon name="tag" color="grey-6" size="14px" />
                          <span class="text-caption text-grey-6">编号</span>
                        </div>
                        <div class="text-body2 text-weight-medium q-mt-xs">{{ device.order_number || '未设置' }}</div>
                      </div>
                      <div class="col" style="flex: 1; min-width: 0;">
                        <div class="row items-center q-gutter-x-xs">
                          <q-icon name="wifi" color="grey-6" size="14px" />
                          <span class="text-caption text-grey-6">IP地址</span>
                        </div>
                        <div class="text-body2 text-weight-medium q-mt-xs">{{ device.ip_address || '未知' }}</div>
                      </div>
                    </div>
                  </div>

                  <!-- 注册时间和最后活跃时间 -->
                  <div class="col-12">
                    <div class="row q-gutter-x-md" style="display: flex; flex-wrap: nowrap;">
                      <div class="col" style="flex: 1; min-width: 0;">
                        <div class="row items-center q-gutter-x-xs">
                          <q-icon name="event" color="grey-6" size="14px" />
                          <span class="text-caption text-grey-6">注册时间</span>
                        </div>
                        <div class="text-body2 text-weight-medium q-mt-xs">
                          {{ formatDateTime(device.created_at) }}
                        </div>
                      </div>
                      <div class="col" style="flex: 1; min-width: 0;">
                        <div class="row items-center q-gutter-x-xs">
                          <q-icon name="access_time" color="grey-6" size="14px" />
                          <span class="text-caption text-grey-6">最后活跃</span>
                        </div>
                        <div class="text-body2 text-weight-medium q-mt-xs">
                          {{ formatLastSeenWithYear(device.last_heartbeat) }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- 系统信息 -->
                  <div class="col-12" v-if="device.system_info">
                    <div class="row items-center q-gutter-x-xs q-mb-xs">
                      <q-icon name="computer" color="grey-6" size="14px" />
                      <span class="text-caption text-grey-6">系统信息</span>
                    </div>
                    <div class="text-body2 text-weight-medium" style="line-height: 1.3;">
                      {{ formatSystemInfo(device.system_info) }}
                    </div>
                  </div>
                </div>
              </q-card-section>

              <!-- 资源监控信息（仅在线设备显示） -->
              <q-card-section v-if="isDeviceOnline(device.last_heartbeat) && hasResourceData(device)" class="q-pt-none q-pb-md">
                <q-separator class="q-mb-md" />
                <div class="row items-center q-gutter-x-xs q-mb-md">
                  <q-icon name="analytics" color="primary" size="16px" />
                  <span class="text-body2 text-weight-medium text-primary">资源监控</span>
                </div>

                <div class="row q-gutter-md">
                  <!-- CPU使用率 -->
                  <div class="col-6 col-sm-3">
                    <div class="text-center">
                      <div class="text-caption text-grey-7 q-mb-xs">CPU</div>
                      <q-circular-progress
                        :value="device.cpu_usage"
                        :color="getResourceColor(device.cpu_usage)"
                        size="50px"
                        :thickness="0.15"
                        track-color="grey-3"
                        class="q-mb-xs"
                      >
                        <div class="text-caption text-weight-bold">
                          {{ formatPercentage(device.cpu_usage) }}
                        </div>
                      </q-circular-progress>
                    </div>
                  </div>

                  <!-- 内存使用率 -->
                  <div class="col-6 col-sm-3">
                    <div class="text-center">
                      <div class="text-caption text-grey-7 q-mb-xs">内存</div>
                      <q-circular-progress
                        :value="device.memory_usage"
                        :color="getResourceColor(device.memory_usage)"
                        size="50px"
                        :thickness="0.15"
                        track-color="grey-3"
                        class="q-mb-xs"
                      >
                        <div class="text-caption text-weight-bold">
                          {{ formatPercentage(device.memory_usage) }}
                        </div>
                      </q-circular-progress>
                    </div>
                  </div>

                  <!-- 磁盘使用率 -->
                  <div class="col-6 col-sm-3">
                    <div class="text-center">
                      <div class="text-caption text-grey-7 q-mb-xs">磁盘</div>
                      <q-circular-progress
                        :value="device.disk_usage"
                        :color="getResourceColor(device.disk_usage)"
                        size="50px"
                        :thickness="0.15"
                        track-color="grey-3"
                        class="q-mb-xs"
                      >
                        <div class="text-caption text-weight-bold">
                          {{ formatPercentage(device.disk_usage) }}
                        </div>
                      </q-circular-progress>
                    </div>
                  </div>

                  <!-- 温度 -->
                  <div class="col-6 col-sm-3" v-if="device.temperature">
                    <div class="text-center">
                      <div class="text-caption text-grey-7 q-mb-xs">温度</div>
                      <q-circular-progress
                        :value="Math.min(device.temperature, 100)"
                        :color="getTemperatureColor(device.temperature)"
                        size="50px"
                        :thickness="0.15"
                        track-color="grey-3"
                        class="q-mb-xs"
                      >
                        <div class="text-caption text-weight-bold">
                          {{ formatTemperature(device.temperature) }}
                        </div>
                      </q-circular-progress>
                    </div>
                  </div>
                </div>
              </q-card-section>

              <!-- 备注信息 -->
              <q-card-section class="q-pt-none q-pb-md">
                <q-separator class="q-mb-md" />
                <div class="row items-center q-gutter-x-xs q-mb-sm">
                  <q-icon name="comment" color="amber-7" size="16px" />
                  <span class="text-body2 text-weight-medium text-amber-7">备注</span>
                </div>
                <div v-if="device.remarks" class="text-body2 text-grey-8 q-pl-md remarks-with-content">
                  {{ device.remarks }}
                </div>
                <div v-else class="text-body2 text-grey-5 q-pl-md remarks-empty">
                  暂无备注
                </div>
              </q-card-section>

              <!-- 操作按钮 -->
              <q-card-actions class="q-pt-none q-pb-md q-px-md">
                <q-space />
                <div class="row q-gutter-xs">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="edit"
                    size="sm"
                    class="q-pa-xs"
                    @click.stop="editDevice(device)"
                  >
                    <q-tooltip class="bg-primary">编辑设备</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    round
                    color="info"
                    icon="info"
                    size="sm"
                    class="q-pa-xs"
                    @click.stop="viewDevice(device)"
                  >
                    <q-tooltip class="bg-info">查看详情</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    round
                    color="negative"
                    icon="delete"
                    size="sm"
                    class="q-pa-xs"
                    @click.stop="deleteDevice(device)"
                  >
                    <q-tooltip class="bg-negative">删除设备</q-tooltip>
                  </q-btn>
                </div>
              </q-card-actions>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- 添加设备对话框 -->
    <q-dialog v-model="showAddDeviceDialog">
      <q-card style="min-width: 400px">
        <q-card-section>
          <div class="text-h6">添加新设备</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit="addDevice" class="q-gutter-md">
            <q-input
              v-model="newDevice.name"
              label="设备名称"
              outlined
              :rules="[(val) => !!val || '请输入设备名称']"
            />
            <q-input
              v-model="newDevice.ip"
              label="IP地址"
              outlined
              :rules="[(val) => !!val || '请输入IP地址']"
            />
            <q-input v-model="newDevice.remarks" label="备注" outlined type="textarea" />
          </q-form>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="取消" v-close-popup />
          <q-btn color="primary" label="添加" @click="addDevice" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- 编辑设备对话框 -->
    <q-dialog
      v-model="showEditDeviceDialog"
      :maximized="$q.screen.lt.sm"
      :persistent="false"
    >
      <q-card class="edit-device-dialog">
        <q-card-section class="q-pb-sm">
          <div class="text-h6">编辑设备</div>
          <div class="text-caption text-grey-6" v-if="editingDevice">
            {{ editingDevice.device_name }} ({{ editingDevice.device_id }})
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pt-md scroll-section">
          <q-form class="q-gutter-sm">
            <q-input
              v-model.number="editDeviceForm.order_number"
              label="设备编号"
              outlined
              dense
              type="number"
              min="0"
              hint="设置设备的显示编号，0表示不显示编号"
            />
            <q-input
              :model-value="formattedDateTime"
              label="注册时间"
              outlined
              dense
              readonly
              hint="设备的注册时间"
            >
              <template v-slot:prepend>
                <q-icon name="schedule" />
              </template>
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy
                    ref="dateTimePopup"
                    transition-show="scale"
                    transition-hide="scale"
                    :breakpoint="600"
                  >
                    <q-card class="simple-datetime-picker">
                      <q-card-section class="q-pb-sm">
                        <div class="text-h6">修改注册时间</div>
                        <div class="text-caption text-grey-6">
                          当前：{{ formattedDateTime || '未设置' }}
                        </div>
                      </q-card-section>

                      <q-separator />

                      <q-card-section class="q-py-md">
                        <!-- 快速预设选项 -->
                        <div class="quick-preset-section q-mb-md">
                          <div class="text-subtitle2 q-mb-sm">常用时间</div>
                          <div class="row q-gutter-xs">
                            <q-chip
                              v-for="preset in datePresets"
                              :key="preset.label"
                              @click="selectPreset(preset.value)"
                              clickable
                              color="primary"
                              outline
                              size="sm"
                            >
                              {{ preset.label }}
                            </q-chip>
                          </div>
                        </div>

                        <!-- 简化的日期时间输入 -->
                        <div class="datetime-inputs">
                          <div class="row q-gutter-sm">
                            <div class="col">
                              <q-input
                                v-model="dateTimeProxy.date"
                                label="日期"
                                mask="####-##-##"
                                fill-mask
                                outlined
                                dense
                                :rules="[dateRule]"
                              >
                                <template v-slot:append>
                                  <q-icon name="event" class="cursor-pointer">
                                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                      <q-date
                                        v-model="dateTimeProxy.date"
                                        mask="YYYY-MM-DD"
                                        :locale="dateLocale"
                                        :options="dateOptions"
                                        color="primary"
                                      />
                                    </q-popup-proxy>
                                  </q-icon>
                                </template>
                              </q-input>
                            </div>

                            <div class="col">
                              <q-input
                                v-model="dateTimeProxy.time"
                                label="时间"
                                mask="##:##"
                                fill-mask
                                outlined
                                dense
                                :rules="[timeRule]"
                              >
                                <template v-slot:append>
                                  <q-icon name="schedule" class="cursor-pointer">
                                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                      <q-time
                                        v-model="dateTimeProxy.time"
                                        mask="HH:mm"
                                        format24h
                                        color="primary"
                                      />
                                    </q-popup-proxy>
                                  </q-icon>
                                </template>
                              </q-input>
                            </div>
                          </div>
                        </div>
                      </q-card-section>

                      <q-separator />

                      <q-card-actions align="right" class="q-pa-md">
                        <q-btn
                          label="取消"
                          flat
                          v-close-popup
                        />
                        <q-btn
                          label="确定"
                          color="primary"
                          unelevated
                          @click="handleDateTimeConfirm"
                          v-close-popup
                        />
                      </q-card-actions>
                    </q-card>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
            <q-input
              v-model="editDeviceForm.remarks"
              label="备注"
              outlined
              dense
              type="textarea"
              :rows="$q.screen.lt.sm ? 2 : 3"
              hint="设备的备注信息"
            />
          </q-form>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md">
          <q-btn
            flat
            label="取消"
            @click="cancelDeviceEdit"
            :class="$q.screen.lt.sm ? 'q-mr-sm' : ''"
          />
          <q-btn
            color="primary"
            label="保存"
            @click="saveDeviceEdit"
            unelevated
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useDeviceStore } from '@/stores/device';
import type { Device } from '../types/device';
import {
  formatLastSeenWithYear,
  formatPercentage,
  formatTemperature,
  formatSystemInfo,
  getResourceColor,
  getDeviceStatusColor,
  formatDateTime,
} from '@/utils/format';

const $q = useQuasar();

const deviceStore = useDeviceStore();

// 响应式数据
const loading = ref(false);
const searchQuery = ref('');
const searchType = ref('all');
const statusFilter = ref<'all' | 'online' | 'offline'>('all');
const showAddDeviceDialog = ref(false);
const showEditDeviceDialog = ref(false);
const newDevice = ref({
  name: '',
  ip: '',
  remarks: '',
});
const editingDevice = ref<Device | null>(null);
const editDeviceForm = ref({
  order_number: 0,
  remarks: '',
  created_at: '',
});

// 日期时间代理对象
const dateTimeProxy = ref({
  date: '',
  time: '',
});

// 中文本地化配置
const dateLocale = {
  days: ['周日', '周一', '周二', '周三', '周四', '周五', '周六'],
  daysShort: ['日', '一', '二', '三', '四', '五', '六'],
  months: [
    '一月', '二月', '三月', '四月', '五月', '六月',
    '七月', '八月', '九月', '十月', '十一月', '十二月'
  ],
  monthsShort: [
    '1月', '2月', '3月', '4月', '5月', '6月',
    '7月', '8月', '9月', '10月', '11月', '12月'
  ]
};

// 日期选择限制 - 不能选择未来日期
const dateOptions = (date: string) => {
  const today = new Date();
  const selectedDate = new Date(date);
  return selectedDate <= today;
};

// 快速预设时间选项
const datePresets = [
  { label: '今天', value: () => new Date() },
  { label: '昨天', value: () => new Date(Date.now() - 24 * 60 * 60 * 1000) },
  { label: '上周', value: () => new Date(Date.now() - 7 * 24 * 60 * 60 * 1000) },
  { label: '上月', value: () => new Date(Date.now() - 30 * 24 * 60 * 60 * 1000) }
];

// 输入验证规则
const dateRule = (val: string) => {
  if (!val) return '请输入日期';
  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
  if (!dateRegex.test(val)) return '日期格式错误，请使用YYYY-MM-DD';
  const date = new Date(val);
  if (isNaN(date.getTime())) return '无效的日期';
  if (date > new Date()) return '不能选择未来日期';
  return true;
};

const timeRule = (val: string) => {
  if (!val) return '请输入时间';
  const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
  if (!timeRegex.test(val)) return '时间格式错误，请使用HH:MM';
  return true;
};


// 分页配置
const pagination = ref({
  sortBy: 'name',
  descending: false,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0,
});

// 搜索类型选项
const searchTypeOptions = [
  { label: '全部', value: 'all' },
  { label: '设备名称', value: 'name' },
  { label: 'IP地址', value: 'ip' },
  { label: '设备ID', value: 'id' },
  { label: '编号', value: 'number' },
  { label: '备注', value: 'remarks' }
];

// 计算属性
const deviceStats = computed(() => {
  return deviceStore.stats;
});

const searchPlaceholder = computed(() => {
  switch (searchType.value) {
    case 'name':
      return '请输入设备名称';
    case 'ip':
      return '请输入IP地址';
    case 'id':
      return '请输入设备ID';
    case 'number':
      return '请输入编号';
    case 'remarks':
      return '请输入备注';
    default:
      return '搜索设备名称、IP地址、设备ID、编号或备注';
  }
});

// 格式化显示时间
const formattedDateTime = computed(() => {
  if (!editDeviceForm.value.created_at) return '';
  const date = new Date(editDeviceForm.value.created_at);
  if (isNaN(date.getTime())) return editDeviceForm.value.created_at;

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');

  return `${year}年${month}月${day}日 ${hours}:${minutes}`;
});

const filteredDevicesCount = computed(() => {
  return deviceStore.devices.filter((device) => {
    return isDeviceVisible(device);
  }).length;
});

// 判断设备是否在线
const isDeviceOnline = (lastHeartbeat: string | null, timeoutMinutes = 5): boolean => {
  if (!lastHeartbeat) return false;
  const now = new Date();
  const heartbeatTime = new Date(lastHeartbeat);
  const diffInMinutes = (now.getTime() - heartbeatTime.getTime()) / (1000 * 60);
  return diffInMinutes <= timeoutMinutes;
};

// 获取设备状态样式类
const getDeviceStatusClass = (device: Device) => ({
  'device-online': isDeviceOnline(device.last_heartbeat),
  'device-offline': !isDeviceOnline(device.last_heartbeat),
  'device-warning': device.status === 'warning',
});

// 获取设备图标
const getDeviceIcon = (device: Device) => {
  if (device.status === 'warning') return 'warning';
  return isDeviceOnline(device.last_heartbeat) ? 'computer' : 'computer';
};

// 获取设备状态文本
const getDeviceStatusText = (device: Device) => {
  if (device.status === 'warning') return '警告';
  return isDeviceOnline(device.last_heartbeat) ? '在线' : '离线';
};

// 检查是否有资源数据
const hasResourceData = (device: Device) => {
  return device.cpu_usage > 0 || device.memory_usage > 0 || device.disk_usage > 0;
};

// 获取温度颜色
const getTemperatureColor = (temp: number) => {
  if (temp >= 80) return 'negative';
  if (temp >= 70) return 'warning';
  return 'positive';
};

// 判断设备是否可见
const isDeviceVisible = (device: Device) => {
  // 首先检查状态筛选
  if (statusFilter.value !== 'all') {
    const deviceOnline = isDeviceOnline(device.last_heartbeat);
    if (statusFilter.value === 'online' && !deviceOnline) {
      return false;
    }
    if (statusFilter.value === 'offline' && deviceOnline) {
      return false;
    }
  }

  // 然后检查搜索筛选
  if (!searchQuery.value) {
    return true;
  }
  const query = searchQuery.value.toLowerCase();

  if (searchType.value === 'all') {
    return (
      device.device_name.toLowerCase().includes(query) ||
      (device.ip_address && device.ip_address.toLowerCase().includes(query)) ||
      (device.remarks && device.remarks.toLowerCase().includes(query)) ||
      (device.device_id && device.device_id.toLowerCase().includes(query)) ||
      (device.order_number && device.order_number.toString().includes(query))
    );
  } else if (searchType.value === 'name') {
    return device.device_name.toLowerCase().includes(query);
  } else if (searchType.value === 'ip') {
    return device.ip_address && device.ip_address.toLowerCase().includes(query);
  } else if (searchType.value === 'id') {
    return device.device_id && device.device_id.toLowerCase().includes(query);
  } else if (searchType.value === 'number') {
    return device.order_number && device.order_number.toString().includes(query);
  } else if (searchType.value === 'remarks') {
    return device.remarks && device.remarks.toLowerCase().includes(query);
  }
  return false;
};

// 状态筛选方法
const filterByStatus = (status: 'all' | 'online' | 'offline') => {
  statusFilter.value = status;
  // 清空搜索条件，专注于状态筛选
  searchQuery.value = '';
  searchType.value = 'all';
};

// 快速预设选择
const selectPreset = (presetFn: () => Date) => {
  const date = presetFn();
  const isoString = date.toISOString();
  const dateStr = isoString.split('T')[0] || '';
  const timeString = date.toTimeString();
  const timeStr = timeString.slice(0, 5) || '00:00';

  dateTimeProxy.value.date = dateStr;
  dateTimeProxy.value.time = timeStr;
};

// 确认日期时间选择
const handleDateTimeConfirm = () => {
  if (dateTimeProxy.value.date && dateTimeProxy.value.time) {
    editDeviceForm.value.created_at = `${dateTimeProxy.value.date}T${dateTimeProxy.value.time}`;
  } else if (dateTimeProxy.value.date) {
    editDeviceForm.value.created_at = `${dateTimeProxy.value.date}T00:00`;
  }
};

// 方法
const onRequest = async (props: { pagination: { page: number; rowsPerPage: number } }) => {
  const { page, rowsPerPage } = props.pagination;

  loading.value = true;

  try {
    await deviceStore.fetchDevices({
      page,
      limit: rowsPerPage,
    });

    pagination.value.page = page;
    pagination.value.rowsPerPage = rowsPerPage;
    pagination.value.rowsNumber = deviceStore.stats.total;
  } catch {
    // 获取设备列表失败
  } finally {
    loading.value = false;
  }
};

const addDevice = async () => {
  try {
    // TODO: 实现添加设备功能
    // 添加设备
    showAddDeviceDialog.value = false;
    newDevice.value = { name: '', ip: '', remarks: '' };
    await onRequest({ pagination: { page: 1, rowsPerPage: 100 } });
  } catch {
    // 添加设备失败
  }
};

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const viewDevice = (_device: Device) => {
  // TODO: 实现查看设备详情
  // 查看设备
};

const editDevice = (device: Device) => {
  editingDevice.value = device;
  // 格式化日期时间为 datetime-local 输入格式
  // 使用设备的实际注册时间，保持本地时区
  const createdAt = device.created_at
    ? new Date(
        new Date(device.created_at).getTime() -
          new Date(device.created_at).getTimezoneOffset() * 60000,
      )
        .toISOString()
        .slice(0, 16)
    : '';

  editDeviceForm.value = {
    order_number: device.order_number || 0,
    remarks: device.remarks || '',
    created_at: createdAt,
  };

  // 初始化日期时间代理对象
  if (createdAt) {
    const [datePart, timePart] = createdAt.split('T');
    dateTimeProxy.value = {
      date: datePart || '',
      time: timePart || '',
    };
  } else {
    dateTimeProxy.value = {
      date: '',
      time: '',
    };
  }

  showEditDeviceDialog.value = true;
};

const saveDeviceEdit = async () => {
  if (!editingDevice.value) return;

  try {
    // 调用API更新设备信息
    await deviceStore.updateDevice(editingDevice.value.device_id, {
      remarks: editDeviceForm.value.remarks,
      order_number: editDeviceForm.value.order_number,
      created_at: editDeviceForm.value.created_at,
    });

    showEditDeviceDialog.value = false;
    editingDevice.value = null;

    // 显示成功提示
    $q.notify({
      type: 'positive',
      message: '设备信息更新成功',
      position: 'top',
      timeout: 2000,
    });
  } catch (error: unknown) {
    // 显示错误提示
    $q.notify({
      type: 'negative',
      message: (error as Error)?.message || '更新设备信息失败',
      position: 'top',
      timeout: 2000,
    });
  }
};

const cancelDeviceEdit = () => {
  showEditDeviceDialog.value = false;
  editingDevice.value = null;
  editDeviceForm.value = {
    order_number: 0,
    remarks: '',
    created_at: '',
  };
};

const deleteDevice = (device: Device) => {
  // 显示确认对话框
  $q.dialog({
    title: '确认删除',
    message: `确定要删除设备 "${device.device_name}" 吗？\n\n此操作不可撤销！`,
    cancel: true,
    persistent: true,
    color: 'negative',
  }).onOk(() => {
    void (async () => {
      try {
        await deviceStore.removeDevice(device.device_id);

        // 显示成功提示
        $q.notify({
          type: 'positive',
          message: '设备删除成功',
          position: 'top',
          timeout: 2000,
        });
      } catch (error: unknown) {
        // 显示错误提示
        $q.notify({
          type: 'negative',
          message: (error as Error)?.message || '删除设备失败',
          position: 'top',
          timeout: 2000,
        });
      }
    })();
  });
};

// 生命周期
onMounted(async () => {
  // 获取所有设备，使用最大允许的limit值
  // fetchDevices已经会获取统计数据，所以不需要单独调用fetchStats
  await onRequest({ pagination: { page: 1, rowsPerPage: 100 } });
});
</script>

<style scoped>
.remarks-with-content {
  border-left: 3px solid #FFC107;
  background: #FFF8E1;
  padding: 8px 12px;
  border-radius: 4px;
}

.remarks-empty {
  border-left: 3px solid #E0E0E0;
  background: #FAFAFA;
  padding: 8px 12px;
  border-radius: 4px;
  font-style: italic;
}

.q-card {
  box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.q-card.cursor-pointer:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.q-card.cursor-pointer {
  user-select: none;
}

/* 激活状态的卡片样式 */
.q-card.bg-primary,
.q-card.bg-positive,
.q-card.bg-negative {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* 编辑设备对话框样式 */
.edit-device-dialog {
  min-width: 280px;
  max-width: 500px;
  width: 90vw;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
}

@media (min-width: 600px) {
  .edit-device-dialog {
    width: 480px;
  }
}

@media (max-width: 599px) {
  .edit-device-dialog {
    width: 100vw;
    max-width: 100vw;
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
  }
}

.scroll-section {
  flex: 1;
  overflow-y: auto;
  max-height: 60vh;
}

@media (max-width: 599px) {
  .scroll-section {
    max-height: calc(100vh - 140px);
  }
}

/* 优化表单间距 */
.edit-device-dialog .q-form {
  gap: 12px;
}

@media (max-width: 599px) {
  .edit-device-dialog .q-form {
    gap: 8px;
  }
}

/* 按钮间距优化 */
.edit-device-dialog .q-card-actions .q-btn {
  min-width: 64px;
}

@media (max-width: 599px) {
  .edit-device-dialog .q-card-actions {
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
  }

  .edit-device-dialog .q-card-actions .q-btn {
    flex: 1;
    max-width: 120px;
  }
}

/* 日期时间选择器样式 */
.datetime-picker-card {
  min-width: 320px;
  max-width: 600px;
  width: 90vw;
}

@media (min-width: 768px) {
  .datetime-picker-card {
    width: 560px;
  }
}

@media (max-width: 767px) {
  .datetime-picker-card {
    width: 100vw;
    max-width: 100vw;
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
  }
}

.datetime-content {
  max-height: 75vh;
  overflow-y: auto;
  min-height: 400px;
}

@media (max-width: 767px) {
  .datetime-content {
    max-height: calc(100vh - 120px);
    min-height: calc(100vh - 140px);
  }
}

@media (max-width: 480px) {
  .datetime-content {
    min-height: calc(100vh - 120px);
  }
}

.datetime-picker-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
  align-items: center;
  justify-content: flex-start;
  min-height: 350px;
}

@media (min-width: 768px) {
  .datetime-picker-container {
    flex-direction: row;
    gap: 32px;
    align-items: flex-start;
    min-height: 320px;
  }
}

.date-picker-section,
.time-picker-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 0;
}

.date-picker-section .q-date,
.time-picker-section .q-time {
  align-self: center;
  width: auto;
  max-width: 100%;
}

/* 移动端优化 */
@media (max-width: 767px) {
  .datetime-picker-container {
    gap: 20px;
    min-height: 450px;
  }

  .date-picker-section,
  .time-picker-section {
    width: 100%;
    max-width: 320px;
  }

  .date-picker-section .q-date,
  .time-picker-section .q-time {
    width: 100%;
    max-width: 300px;
  }
}

/* 确保日期时间控件在小屏幕上正确显示 */
.datetime-picker-card .q-date,
.datetime-picker-card .q-time {
  font-size: 14px;
  min-height: auto;
}

/* 修复时间选择器显示问题 */
.datetime-picker-card .q-time {
  min-width: 250px;
  min-height: 280px;
}

.datetime-picker-card .q-time .q-time__content {
  min-height: 200px;
}

.datetime-picker-card .q-time .q-time__clock {
  width: 200px !important;
  height: 200px !important;
  min-width: 200px;
  min-height: 200px;
}

.datetime-picker-card .q-time .q-time__clock .q-time__clock-position {
  width: 100%;
  height: 100%;
}

/* 确保时间选择器的数字显示完整 */
.datetime-picker-card .q-time .q-btn {
  min-width: 32px !important;
  min-height: 32px !important;
  font-size: 14px;
  line-height: 1;
}

.datetime-picker-card .q-time__actions {
  padding: 8px;
  justify-content: center;
}

.datetime-picker-card .q-time__header {
  min-height: 48px;
  font-size: 24px;
  padding: 12px;
}

@media (max-width: 480px) {
  .datetime-picker-card .q-date,
  .datetime-picker-card .q-time {
    font-size: 13px;
  }

  .datetime-picker-card .q-time {
    min-width: 200px;
    min-height: 240px;
  }

  .datetime-picker-card .q-time .q-time__clock {
    width: 160px !important;
    height: 160px !important;
    min-width: 160px;
    min-height: 160px;
  }

  .datetime-picker-card .q-time .q-btn {
    min-width: 28px !important;
    min-height: 28px !important;
    font-size: 12px;
  }

  .datetime-picker-card .q-time__header {
    font-size: 20px;
    min-height: 40px;
    padding: 8px;
  }
}

/* 简化版日期时间选择器样式 */
.simple-datetime-picker {
  min-width: 320px;
  max-width: 500px;
  width: 90vw;
}

@media (min-width: 600px) {
  .simple-datetime-picker {
    width: 460px;
  }
}

@media (max-width: 599px) {
  .simple-datetime-picker {
    width: 95vw;
    max-width: 95vw;
  }
}

/* 快速预设选项样式 */
.quick-preset-section .q-chip {
  font-size: 12px;
  margin-bottom: 4px;
}

.quick-preset-section .q-chip:hover {
  background-color: rgba(var(--q-primary-rgb), 0.1);
}

/* 日期时间输入框样式 */
.datetime-inputs {
  max-width: 100%;
}

.datetime-inputs .q-input {
  min-width: 0;
}

/* 确保弹出的日期时间选择器不会太大 */
.simple-datetime-picker .q-popup-proxy .q-date,
.simple-datetime-picker .q-popup-proxy .q-time {
  max-width: 300px;
  font-size: 14px;
}

@media (max-width: 480px) {
  .simple-datetime-picker .q-popup-proxy .q-date,
  .simple-datetime-picker .q-popup-proxy .q-time {
    max-width: 280px;
    font-size: 13px;
  }
}

/* 优化按钮组布局 */
.simple-datetime-picker .q-card-actions {
  min-height: 52px;
}

/* 统计卡片网格布局 */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

/* 移动端优化 - 两列布局 */
@media (max-width: 599px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .stats-card__content {
    padding: 12px;
  }

  .stats-card__number {
    font-size: 1.5rem;
    line-height: 1.2;
  }

  .stats-card__label {
    font-size: 0.75rem;
    line-height: 1.3;
  }

  .stats-card__icon {
    margin-bottom: 8px;
  }

  .stats-card__icon .q-icon {
    font-size: 20px;
  }
}

/* 平板端优化 */
@media (min-width: 600px) and (max-width: 1023px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
}

/* 桌面端优化 */
@media (min-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
  }
}

/* 搜索框桌面端宽度限制 */
@media (min-width: 1024px) {
  .col-sm .q-input {
    max-width: 400px;
  }

  .col-sm-auto .q-select {
    max-width: 150px;
  }
}

/* 确保搜索区域右对齐 */
@media (min-width: 600px) {
  .row.justify-end {
    justify-content: flex-end !important;
    margin-left: auto;
  }

  .row.justify-end .col-sm {
    flex: 0 0 auto;
    max-width: 300px;
  }

  .row.justify-end .col-sm-auto {
    flex: 0 0 auto;
  }
}

/* 设备卡片样式 */
.device-card {
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(0, 0, 0, 0.06);
  background: #ffffff;
  overflow: hidden;
}

.device-card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  transform: translateY(-4px);
  border-color: rgba(25, 118, 210, 0.2);
}

.device-card.device-online {
  border-left: 4px solid #4caf50;
}

.device-card.device-offline {
  border-left: 4px solid #f44336;
}

.device-card.device-warning {
  border-left: 4px solid #ff9800;
}

/* 统计卡片样式 */
.stats-card {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.stats-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.stats-card--clickable {
  cursor: pointer;
}

.stats-card--clickable:active {
  transform: translateY(0);
}

/* 激活状态 */
.stats-card--active {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: white;
  box-shadow: 0 4px 20px rgba(25, 118, 210, 0.3);
}

.stats-card--active .stats-card__number {
  color: white !important;
}

.stats-card--active .stats-card__label {
  color: rgba(255, 255, 255, 0.8) !important;
}

.stats-card--active.stats-card--online {
  background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
  box-shadow: 0 4px 20px rgba(76, 175, 80, 0.3);
}

.stats-card--active.stats-card--offline {
  background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
  box-shadow: 0 4px 20px rgba(244, 67, 54, 0.3);
}

/* 卡片内容布局 */
.stats-card__content {
  text-align: center;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

@media (max-width: 599px) {
  .stats-card__content {
    padding: 12px 12px;
    gap: 4px;
  }
}

/* 图标样式 */
.stats-card__icon {
  opacity: 0.7;
  margin-bottom: 4px;
}

.stats-card--active .stats-card__icon {
  opacity: 0.9;
}

/* 数字样式 */
.stats-card__number {
  font-size: 2.5rem;
  font-weight: 600;
  line-height: 1;
  margin: 4px 0;
  color: var(--q-dark);
}

@media (max-width: 599px) {
  .stats-card__number {
    font-size: 2rem;
  }
}

@media (min-width: 1200px) {
  .stats-card__number {
    font-size: 2.8rem;
  }
}

/* 标签样式 */
.stats-card__label {
  font-size: 0.875rem;
  font-weight: 500;
  opacity: 0.8;
  color: var(--q-dark);
}

@media (max-width: 599px) {
  .stats-card__label {
    font-size: 0.8rem;
  }
}
</style>
