const modalText = document.getElementById('modalMessage');
modalText.innerHTML = '<p> OTP sent to your email! Please verify </p>';

const showModal = new bootstrap.Modal(document.getElementById('infoModal'));
showModal.show();