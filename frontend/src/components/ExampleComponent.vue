<template>
  <q-card class="example-card" flat bordered>
    <q-card-section>
      <div class="text-h6 q-mb-md">{{ title }}</div>
      
      <q-list bordered separator>
        <q-item 
          v-for="todo in todos" 
          :key="todo.id" 
          clickable 
          @click="increment"
          class="q-py-sm"
        >
          <q-item-section>
            <q-item-label>{{ todo.id }} - {{ todo.content }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="chevron_right" color="grey-5" />
          </q-item-section>
        </q-item>
      </q-list>
      
      <div class="q-mt-md">
        <q-chip color="primary" text-color="white" icon="list">
          Count: {{ todoCount }} / {{ meta.totalCount }}
        </q-chip>
        
        <q-chip 
          :color="active ? 'positive' : 'negative'" 
          text-color="white" 
          :icon="active ? 'check_circle' : 'cancel'"
          class="q-ml-sm"
        >
          Active: {{ active ? 'yes' : 'no' }}
        </q-chip>
        
        <q-chip color="info" text-color="white" icon="mouse" class="q-ml-sm">
          Clicks: {{ clickCount }}
        </q-chip>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Todo, Meta } from './models';

interface Props {
  title: string;
  todos?: Todo[];
  meta: Meta;
  active: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  todos: () => [],
});

const clickCount = ref(0);
function increment() {
  clickCount.value += 1;
  return clickCount.value;
}

const todoCount = computed(() => props.todos.length);
</script>
