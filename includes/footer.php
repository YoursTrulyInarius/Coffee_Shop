
            </div><!-- /.page-body -->
        </main><!-- /.main-content -->
    </div><!-- /.app-wrapper -->

    <!-- Confirm Dialog (shared) -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal" style="max-width: 340px;">
            <div class="modal-body">
                <div class="confirm-body">
                    <div class="confirm-icon">
                        <svg class="icon-svg" style="width: 64px; height: 64px; color: var(--warning); opacity: 0.8;" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm1 14h-2v-2h2v2zm0-4h-2V8h2v4z"/></svg>
                    </div>
                    <p id="confirmMessage">Are you sure?</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline btn-sm" onclick="closeModal('confirmModal')">Cancel</button>
                <button class="btn btn-danger btn-sm" id="confirmYes">Yes, Proceed</button>
            </div>
        </div>
    </div>


</body>
</html>
