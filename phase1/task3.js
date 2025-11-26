// Phase 2 JavaScript skeleton for weekly pages (Task 3)

// Validate comment form before submitting
function validateComment() {
  const textarea = document.querySelector('.comment-box textarea');
  if (!textarea.value.trim()) {
    alert('Comment cannot be empty');
    return false;
  }
  return true;
}

// Handle comment submission (Phase 2 placeholder)
function submitComment() {
  if (!validateComment()) return;

  // Temporary feedback until backend is connected
  alert('Comment submitted successfully ✅');

  // Clear input
  document.querySelector('.comment-box textarea').value = '';
}

// Export functions (DO NOT CHANGE)
module.exports = {
  validateComment,
  submitComment
};
