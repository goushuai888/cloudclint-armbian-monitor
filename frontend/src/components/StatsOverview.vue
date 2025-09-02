<template>
  <div class="stats-overview">
    <div class="stats-grid">
      <!-- 总设备数 -->
      <q-card class="stats-card stats-primary">
        <q-card-section class="stats-content">
          <div class="stats-icon">
            <q-icon name="devices" />
          </div>
          <div class="stats-info">
            <div class="stats-number">{{ formatNumber(stats.total) }}</div>
            <div class="stats-label">总设备</div>
          </div>
        </q-card-section>
      </q-card>

      <!-- 在线设备数 -->
      <q-card class="stats-card stats-success">
        <q-card-section class="stats-content">
          <div class="stats-icon">
            <q-icon name="check_circle" />
          </div>
          <div class="stats-info">
            <div class="stats-number">{{ formatNumber(stats.online) }}</div>
            <div class="stats-label">在线设备</div>
          </div>
          <div class="stats-trend" v-if="showTrend && (onlineTrend || 0) !== 0">
            <div class="trend-container">
              <q-icon
                :name="(onlineTrend || 0) > 0 ? 'trending_up' : 'trending_down'"
                :color="(onlineTrend || 0) > 0 ? 'positive' : 'negative'"
                size="sm"
              />
              <span :class="{ 'trend-positive': (onlineTrend || 0) > 0, 'trend-negative': (onlineTrend || 0) < 0 }">
                {{ Math.abs(onlineTrend || 0) }}
              </span>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- 离线设备数 -->
      <q-card class="stats-card stats-danger">
        <q-card-section class="stats-content">
          <div class="stats-icon">
            <q-icon name="cancel" />
          </div>
          <div class="stats-info">
            <div class="stats-number">{{ formatNumber(stats.offline) }}</div>
            <div class="stats-label">离线设备</div>
          </div>
          <div class="stats-trend" v-if="showTrend && (offlineTrend || 0) !== 0">
            <div class="trend-container">
              <q-icon
                :name="(offlineTrend || 0) > 0 ? 'trending_up' : 'trending_down'"
                :color="(offlineTrend || 0) > 0 ? 'negative' : 'positive'"
                size="sm"
              />
              <span :class="{ 'trend-negative': (offlineTrend || 0) > 0, 'trend-positive': (offlineTrend || 0) < 0 }">
                {{ Math.abs(offlineTrend || 0) }}
              </span>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- 在线率 -->
      <q-card class="stats-card stats-info">
        <q-card-section class="stats-content">
          <div class="stats-icon">
            <q-icon name="analytics" />
          </div>
          <div class="stats-info">
            <div class="stats-number">{{ formatPercentage(stats.onlineRate, 1) }}</div>
            <div class="stats-label">在线率</div>
          </div>
          <div class="online-rate-progress">
            <q-linear-progress
              :value="stats.onlineRate / 100"
              :color="getOnlineRateColor(stats.onlineRate)"
              size="4px"
              class="q-mt-sm"
            />
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- 详细统计信息（可选显示） -->
    <div v-if="showDetails && detailStats" class="stats-details q-mt-md">
      <q-card class="details-card">
        <q-card-section class="details-header">
          <div class="details-title">
            <q-icon name="bar_chart" class="q-mr-sm" />
            详细统计
          </div>
          <q-btn
            flat
            dense
            round
            icon="refresh"
            @click="$emit('refresh')"
            title="刷新统计"
            :loading="isLoading"
          />
        </q-card-section>

        <q-card-section class="details-content">
          <div class="details-grid">
            <div class="detail-item">
              <div class="detail-label">平均CPU使用率</div>
              <div class="detail-value">
                {{ formatPercentage(detailStats.avgCpuUsage || 0, 1) }}
              </div>
            </div>

            <div class="detail-item">
              <div class="detail-label">平均内存使用率</div>
              <div class="detail-value">
                {{ formatPercentage(detailStats.avgMemoryUsage || 0, 1) }}
              </div>
            </div>

            <div class="detail-item">
              <div class="detail-label">平均磁盘使用率</div>
              <div class="detail-value">
                {{ formatPercentage(detailStats.avgDiskUsage || 0, 1) }}
              </div>
            </div>

            <div class="detail-item">
              <div class="detail-label">系统运行时间</div>
              <div class="detail-value">
                {{ formatUptime(detailStats.systemUptime || 0) }}
              </div>
            </div>

            <div class="detail-item">
              <div class="detail-label">总心跳数</div>
              <div class="detail-value">
                {{ formatNumber(detailStats.totalHeartbeats || 0) }}
              </div>
            </div>

            <div class="detail-item">
              <div class="detail-label">最后更新</div>
              <div class="detail-value">
                {{ lastUpdated ? formatLastSeen(lastUpdated.toISOString()) : '--' }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { DeviceStats } from '@/types/device'
import { formatNumber, formatPercentage, formatUptime, formatLastSeen } from '@/utils/format'

interface DetailStats {
  avgCpuUsage?: number
  avgMemoryUsage?: number
  avgDiskUsage?: number
  systemUptime?: number
  totalHeartbeats?: number
}

interface Props {
  stats: DeviceStats
  detailStats?: DetailStats
  showDetails?: boolean
  showTrend?: boolean
  isLoading?: boolean
  lastUpdated?: Date
  onlineTrend?: number
  offlineTrend?: number
}

interface Emits {
  refresh: []
}

withDefaults(defineProps<Props>(), {
  showDetails: false,
  showTrend: false,
  isLoading: false,
  onlineTrend: 0,
  offlineTrend: 0
})

defineEmits<Emits>()

// 计算属性
const getOnlineRateColor = (rate: number) => {
  if (rate >= 90) return 'positive'
  if (rate >= 70) return 'warning'
  return 'negative'
}
</script>

<style lang="scss" scoped>
.stats-overview {
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;

    @media (max-width: 768px) {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.75rem;
    }

    @media (max-width: 480px) {
      grid-template-columns: 1fr;
      gap: 0.5rem;
    }
  }

  .stats-card {
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    &.stats-primary {
      border-left: 4px solid $primary;

      .stats-icon {
        background: rgba($primary, 0.1);
        color: $primary;
      }
    }

    &.stats-success {
      border-left: 4px solid $positive;

      .stats-icon {
        background: rgba($positive, 0.1);
        color: $positive;
      }
    }

    &.stats-danger {
      border-left: 4px solid $negative;

      .stats-icon {
        background: rgba($negative, 0.1);
        color: $negative;
      }
    }

    &.stats-info {
      border-left: 4px solid $info;

      .stats-icon {
        background: rgba($info, 0.1);
        color: $info;
      }
    }
  }

  .stats-content {
    display: flex;
    align-items: center;
    padding: 1rem;
    position: relative;

    .stats-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      flex-shrink: 0;

      .q-icon {
        font-size: 24px;
      }
    }

    .stats-info {
      flex: 1;

      .stats-number {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1;
        color: var(--q-color-text);

        @media (max-width: 480px) {
          font-size: 1.5rem;
        }
      }

      .stats-label {
        font-size: 0.75rem;
        color: var(--q-color-grey-6);
        margin-top: 2px;
        font-weight: 500;
      }
    }

    .stats-trend {
      position: absolute;
      top: 8px;
      right: 8px;
      display: flex;
      align-items: center;
      font-size: 0.7rem;
      font-weight: 600;

      .q-icon {
        font-size: 12px;
        margin-right: 2px;
      }

      .trend-positive {
        color: $positive;
      }

      .trend-negative {
        color: $negative;
      }
    }

    .online-rate-progress {
      margin-top: 8px;
    }
  }
}

.stats-details {
  .details-card {
    .details-header {
      display: flex;
      align-items: center;
      justify-between;
      padding: 1rem;
      border-bottom: 1px solid var(--q-color-grey-4);

      .details-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--q-color-text);
        display: flex;
        align-items: center;
      }
    }

    .details-content {
      padding: 1rem;

      .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;

        @media (max-width: 768px) {
          grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 480px) {
          grid-template-columns: 1fr;
        }
      }

      .detail-item {
        text-align: center;

        .detail-label {
          font-size: 0.75rem;
          color: var(--q-color-grey-6);
          margin-bottom: 4px;
          font-weight: 500;
        }

        .detail-value {
          font-size: 1.125rem;
          font-weight: 600;
          color: var(--q-color-text);
        }
      }
    }
  }
}
</style>
