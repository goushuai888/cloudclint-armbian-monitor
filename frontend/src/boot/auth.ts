import { defineBoot } from '#q-app/wrappers';
import { useAuthStore } from 'src/stores/auth';
import { autoMigrate } from 'src/utils/migrationHelper';

export default defineBoot(async () => {
  // 执行数据迁移（如果需要）
  try {
    autoMigrate();
  } catch (error) {
    console.error('数据迁移失败:', error);
  }
  
  // 初始化认证状态
  const authStore = useAuthStore();
  await authStore.initAuth();
});
