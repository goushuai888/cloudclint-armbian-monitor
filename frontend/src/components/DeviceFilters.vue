<template>
  <q-item-section class="device-filters">
    <!-- 快速筛选选项卡 -->
    <q-item-section class="filter-tabs">
      <q-tabs
        v-model="activeTab"
        align="left"
        class="filter-tab-group"
        indicator-color="primary"
        active-color="primary"
        @update:model-value="handleTabChange"
      >
        <q-tab name="all" class="filter-tab">
          <q-item class="tab-content">
            <q-icon name="list" class="tab-icon" />
            <q-item-label class="tab-label">所有设备</q-item-label>
            <q-badge v-if="stats.total > 0" class="tab-badge" color="grey-6">
              {{ stats.total }}
            </q-badge>
          </q-item>
        </q-tab>

        <q-tab name="online" class="filter-tab">
          <q-item class="tab-content">
            <q-icon name="check_circle" class="tab-icon" />
            <q-item-label class="tab-label">在线设备</q-item-label>
            <q-badge v-if="stats.online > 0" class="tab-badge" color="positive">
              {{ stats.online }}
            </q-badge>
          </q-item>
        </q-tab>

        <q-tab name="offline" class="filter-tab">
          <q-item class="tab-content">
            <q-icon name="cancel" class="tab-icon" />
            <q-item-label class="tab-label">离线设备</q-item-label>
            <q-badge v-if="stats.offline > 0" class="tab-badge" color="negative">
              {{ stats.offline }}
            </q-badge>
          </q-item>
        </q-tab>
      </q-tabs>
    </q-item-section>

    <!-- 高级筛选区域 -->
    <q-item-section class="advanced-filters" v-if="showAdvanced">
      <q-item class="filters-row">
        <!-- 搜索框 -->
        <q-input
          v-model="searchKeyword"
          placeholder="搜索设备名称、IP地址或ID..."
          clearable
          dense
          outlined
          class="search-input"
          @keyup.enter="handleSearch"
          @clear="handleSearch"
        >
          <template #prepend>
            <q-icon name="search" />
          </template>
          <template #append>
            <q-btn flat dense round icon="search" @click="handleSearch" title="搜索" />
          </template>
        </q-input>

        <!-- 时间筛选 -->
        <q-btn-dropdown
          :label="dateFilterLabel"
          icon="date_range"
          outline
          dense
          class="date-filter-btn"
        >
          <q-list>
            <q-item
              clickable
              v-close-popup
              @click="setDateFilter('all')"
              :active="!filters.year && !filters.month"
            >
              <q-item-section>所有时间</q-item-section>
            </q-item>

            <q-separator />

            <q-item-label header>按年份筛选</q-item-label>
            <q-item
              v-for="year in availableYears"
              :key="year"
              clickable
              v-close-popup
              @click="setDateFilter('year', year)"
              :active="filters.year === year && !filters.month"
            >
              <q-item-section>{{ year }}年</q-item-section>
            </q-item>

            <q-separator />

            <q-item-label header>按月份筛选（{{ currentYear }}年）</q-item-label>
            <q-item
              v-for="month in 12"
              :key="month"
              clickable
              v-close-popup
              @click="setDateFilter('month', currentYear, month)"
              :active="filters.year === currentYear && filters.month === month"
            >
              <q-item-section>{{ currentYear }}年{{ month }}月</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>

        <!-- 排序选项 -->
        <q-select
          v-model="sortOption"
          :options="sortOptions"
          option-value="value"
          option-label="label"
          dense
          outlined
          class="sort-select"
          @update:model-value="handleSortChange"
        />

        <!-- 清除筛选 -->
        <q-btn
          v-if="hasActiveFilters"
          flat
          dense
          icon="clear"
          color="negative"
          @click="clearAllFilters"
          title="清除所有筛选"
          class="clear-btn"
        >
          清除筛选
        </q-btn>
      </q-item>
    </q-item-section>

    <!-- 筛选切换按钮 -->
    <q-item class="filter-toggle">
      <q-btn
        flat
        dense
        :icon="showAdvanced ? 'expand_less' : 'expand_more'"
        :label="showAdvanced ? '收起筛选' : '展开筛选'"
        @click="showAdvanced = !showAdvanced"
        class="toggle-btn"
      />

      <!-- 活跃筛选指示器 -->
      <q-item-section v-if="hasActiveFilters && !showAdvanced" class="active-filters-indicator">
        <q-chip
          v-if="filters.search"
          removable
          dense
          color="primary"
          text-color="white"
          @remove="removeFilter('search')"
        >
          搜索: {{ filters.search }}
        </q-chip>

        <q-chip
          v-if="filters.year && !filters.month"
          removable
          dense
          color="secondary"
          text-color="white"
          @remove="removeFilter('year')"
        >
          {{ filters.year }}年
        </q-chip>

        <q-chip
          v-if="filters.year && filters.month"
          removable
          dense
          color="secondary"
          text-color="white"
          @remove="removeFilter('month')"
        >
          {{ filters.year }}年{{ filters.month }}月
        </q-chip>
      </q-item-section>
    </q-item>
  </q-item-section>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { DeviceStats, DeviceFilters } from '@/types/device';

interface Props {
  filters: DeviceFilters;
  stats: DeviceStats;
}

interface Emits {
  'update:filters': [filters: DeviceFilters];
  search: [keyword: string];
  clear: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// 响应式数据
const showAdvanced = ref(false);
const searchKeyword = ref(props.filters.search || '');
const activeTab = ref<string>('all');
const sortOption = ref({ value: 'order_desc', label: '按编号降序' });

// 计算属性
const currentYear = computed(() => new Date().getFullYear());

const availableYears = computed(() => {
  const years = [];
  const startYear = 2024; // 根据实际情况调整
  for (let year = currentYear.value; year >= startYear; year--) {
    years.push(year);
  }
  return years;
});

const sortOptions = [
  { value: 'order_desc', label: '按编号降序' },
  { value: 'order_asc', label: '按编号升序' },
  { value: 'name_asc', label: '按名称升序' },
  { value: 'name_desc', label: '按名称降序' },
  { value: 'created_desc', label: '按创建时间降序' },
  { value: 'created_asc', label: '按创建时间升序' },
  { value: 'heartbeat_desc', label: '按最后活跃降序' },
  { value: 'heartbeat_asc', label: '按最后活跃升序' },
];

const dateFilterLabel = computed(() => {
  if (props.filters.year && props.filters.month) {
    return `${props.filters.year}年${props.filters.month}月`;
  }
  if (props.filters.year) {
    return `${props.filters.year}年`;
  }
  return '时间筛选';
});

const hasActiveFilters = computed(() => {
  return !!(
    props.filters.search ||
    props.filters.year ||
    props.filters.month ||
    props.filters.status
  );
});

// 监听器
watch(
  () => props.filters.status,
  (status) => {
    if (status === 'online') {
      activeTab.value = 'online';
    } else if (status === 'offline') {
      activeTab.value = 'offline';
    } else {
      activeTab.value = 'all';
    }
  },
  { immediate: true },
);

// 方法
const handleTabChange = (tabName: string) => {
  const newFilters = { ...props.filters };

  switch (tabName) {
    case 'online':
      newFilters.status = 'online';
      break;
    case 'offline':
      newFilters.status = 'offline';
      break;
    default:
      delete newFilters.status;
  }

  newFilters.page = 1; // 重置分页
  emit('update:filters', newFilters);
};

const handleSearch = () => {
  const newFilters = { ...props.filters };

  if (searchKeyword.value.trim()) {
    newFilters.search = searchKeyword.value.trim();
  } else {
    delete newFilters.search;
  }

  newFilters.page = 1; // 重置分页
  emit('update:filters', newFilters);
  emit('search', searchKeyword.value.trim());
};

const setDateFilter = (type: 'all' | 'year' | 'month', year?: number, month?: number) => {
  const newFilters = { ...props.filters };

  switch (type) {
    case 'all':
      delete newFilters.year;
      delete newFilters.month;
      break;
    case 'year':
      if (year !== undefined) {
        newFilters.year = year;
      }
      delete newFilters.month;
      break;
    case 'month':
      if (year !== undefined) {
        newFilters.year = year;
      }
      if (month !== undefined) {
        newFilters.month = month;
      }
      break;
  }

  newFilters.page = 1; // 重置分页
  emit('update:filters', newFilters);
};

const handleSortChange = () => {
  // 这里可以添加排序逻辑，或者通过emit传递给父组件
  // Sort changed
};

const removeFilter = (filterType: string) => {
  const newFilters = { ...props.filters };

  switch (filterType) {
    case 'search':
      delete newFilters.search;
      searchKeyword.value = '';
      break;
    case 'year':
      delete newFilters.year;
      delete newFilters.month;
      break;
    case 'month':
      delete newFilters.month;
      break;
    case 'status':
      delete newFilters.status;
      activeTab.value = 'all';
      break;
  }

  newFilters.page = 1; // 重置分页
  emit('update:filters', newFilters);
};

const clearAllFilters = () => {
  searchKeyword.value = '';
  activeTab.value = 'all';
  sortOption.value = { value: 'order_desc', label: '按编号降序' };

  const newFilters: DeviceFilters = { page: 1, limit: props.filters.limit || 20 };
  emit('update:filters', newFilters);
  emit('clear');
};
</script>

<style lang="scss" scoped>
.device-filters {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 1rem;

  .filter-tabs {
    .filter-tab-group {
      :deep(.q-tabs__content) {
        overflow-x: auto;
        scrollbar-width: none;
        &::-webkit-scrollbar {
          display: none;
        }
      }

      .filter-tab {
        min-width: auto;
        padding: 0 16px;

        .tab-content {
          display: flex;
          align-items: center;
          gap: 6px;

          .tab-icon {
            font-size: 18px;
          }

          .tab-label {
            font-size: 14px;
            font-weight: 500;

            @media (max-width: 480px) {
              display: none;
            }
          }

          .tab-badge {
            margin-left: 4px;
          }
        }
      }
    }
  }

  .advanced-filters {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--q-color-grey-4);

    .filters-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;

      @media (max-width: 768px) {
        gap: 0.5rem;
      }

      .search-input {
        flex: 1;
        min-width: 200px;

        @media (max-width: 480px) {
          min-width: 100%;
        }
      }

      .date-filter-btn {
        min-width: 120px;
      }

      .sort-select {
        min-width: 140px;

        @media (max-width: 480px) {
          min-width: 100%;
        }
      }

      .clear-btn {
        white-space: nowrap;
      }
    }
  }

  .filter-toggle {
    margin-top: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;

    .toggle-btn {
      font-size: 13px;
      color: var(--q-color-grey-6);
    }

    .active-filters-indicator {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;

      .q-chip {
        font-size: 11px;
      }
    }
  }
}
</style>
