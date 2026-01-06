<?php
// Test if error_log() works via web request
error_log("========================================");
error_log("TEST: error_log() called from web request");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("========================================");

echo "Test complete! Check Apache error.log for messages.";
?>
