<?php
/*
 * Client Portal
 * HTML Footer
 */
?>

<!-- Close container -->
</div>

<br>

<!-- Modern Footer -->
<footer class="mt-5 py-4" style="background: linear-gradient(135deg, var(--gray-50), var(--gray-100)); border-top: 1px solid var(--gray-200);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-left mb-3 mb-md-0">
                <p class="mb-2" style="font-weight: 600; color: var(--gray-800);">
                    <i class="fas fa-building mr-2" style="color: var(--primary-color);"></i><?php echo nullable_htmlentities($session_company_name); ?>
                </p>
                <div class="footer-links">
                    <a href="https://samix.one/legal/imprint" target="_blank" rel="noopener noreferrer" class="footer-link">
                        <i class="fas fa-info-circle mr-1"></i>Impressum
                    </a>
                    <a href="https://samix.one/legal/privacy" target="_blank" rel="noopener noreferrer" class="footer-link">
                        <i class="fas fa-shield-alt mr-1"></i>Datenschutz
                    </a>
                    <a href="https://samix.one/legal/terms" target="_blank" rel="noopener noreferrer" class="footer-link">
                        <i class="fas fa-file-contract mr-1"></i>AGB
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-center text-md-right">
                <small class="text-muted">
                    <i class="fas fa-shield-alt mr-1" style="color: var(--success-color);"></i>Secure Portal
                    <span class="mx-2">•</span>
                    <i class="fas fa-clock mr-1"></i><?php echo date('Y'); ?>
                </small>
            </div>
        </div>
    </div>
</footer>


<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/inc_confirm_modal.php'; ?>

<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>

<!-- Bootstrap 4 -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!--- TinyMCE -->
<script src="/plugins/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script src="/js/tinymce_init.js"></script>

<script src="/js/pretty_content.js"></script>

<script src="/js/confirm_modal.js"></script>

<script src="/js/keepalive.js"></script>
