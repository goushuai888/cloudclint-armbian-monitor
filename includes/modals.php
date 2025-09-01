<?php
/**
 * 现代化模态框组件
 * 包含设备管理相关的模态框
 */
?>

<!-- 备注编辑模态框 -->
<div class="modal fade modern-modal" id="remarksModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>编辑设备备注
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <form id="remarksForm">
                    <input type="hidden" id="device_id" name="device_id">
                    <div class="modern-form-group">
                        <label class="modern-form-label" for="remarks">备注信息</label>
                        <textarea class="modern-form-input modern-form-textarea" 
                                  id="remarks" 
                                  name="remarks" 
                                  rows="5" 
                                  placeholder="在此输入设备备注信息..."></textarea>
                        <small class="text-muted">可以输入设备的用途、位置、配置信息等</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="modern-btn modern-btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>取消
                </button>
                <button type="button" class="modern-btn modern-btn-primary" id="saveRemarks">
                    <i class="bi bi-check-circle me-2"></i>保存
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 添加时间编辑模态框 -->
<div class="modal fade modern-modal" id="createdTimeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar-plus me-2"></i>编辑设备添加时间
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <form id="createdTimeForm">
                    <input type="hidden" id="time_device_id" name="device_id">
                    <div class="modern-form-group">
                        <label class="modern-form-label" for="created_at">设备添加时间</label>
                        <input type="datetime-local" 
                               class="modern-form-input" 
                               id="created_at" 
                               name="created_at" 
                               step="1"
                               required>
                        <small class="text-muted">修改设备的初始注册时间，格式：YYYY-MM-DD HH:MM:SS</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="modern-btn modern-btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>取消
                </button>
                <button type="button" class="modern-btn modern-btn-primary" id="saveCreatedTime">
                    <i class="bi bi-check-circle me-2"></i>保存
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 编号编辑模态框 -->
<div class="modal fade modern-modal" id="orderNumberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-sort-numeric-up me-2"></i>编辑设备编号
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <form id="orderNumberForm">
                    <input type="hidden" id="order_device_id" name="device_id">
                    <div class="modern-form-group">
                        <label class="modern-form-label" for="order_number">设备编号</label>
                        <input type="number" 
                               class="modern-form-input" 
                               id="order_number" 
                               name="order_number" 
                               min="0" 
                               max="9999"
                               placeholder="请输入设备编号">
                        <small class="text-muted">设备排序编号，数字越大排序越靠前（0-9999）</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="modern-btn modern-btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>取消
                </button>
                <button type="button" class="modern-btn modern-btn-primary" id="saveOrderNumber">
                    <i class="bi bi-check-circle me-2"></i>保存
                </button>
            </div>
        </div>
    </div>
</div>