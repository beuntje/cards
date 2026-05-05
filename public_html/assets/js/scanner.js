document.addEventListener('DOMContentLoaded', function () {
  var numberInput = document.getElementById('number');
  var typeSelect = document.getElementById('barcode_type');
  var chooser = document.getElementById('add-method-chooser');
  var scanner = null;

  var formatMap = {
    'QR_CODE': 'qr',
    'EAN_13': 'ean_13',
    'EAN_8': 'ean_8',
    'CODE_128': 'code_128',
    'CODE_39': 'code_39',
    'UPC_A': 'ean_13',
    'UPC_E': 'ean_8',
    'ITF': 'itf',
    'CODABAR': 'codabar'
  };

  function applyResult(decodedText, format) {
    // UPC-A is EAN-13 with a leading zero; ensure we store the full 13 digits
    if (format === 'UPC_A') {
      decodedText = decodedText.replace(/^0*/, '');
      decodedText = decodedText.padStart(13, '0');
    }
    // UPC-E is EAN-8 with a leading zero; ensure we store the full 8 digits
    if (format === 'UPC_E') {
      decodedText = decodedText.padStart(8, '0');
    }
    numberInput.value = decodedText;
    if (formatMap[format]) {
      typeSelect.value = formatMap[format];
    }
    // Show the fields so user can verify/edit before submitting
    var manualFields = document.getElementById('manual-fields');
    if (manualFields) manualFields.style.display = '';
    numberInput.required = true;
    numberInput.dispatchEvent(new Event('input'));
    typeSelect.dispatchEvent(new Event('change'));
  }

  if (!chooser) return;

  var panels = document.querySelectorAll('.add-panel');
  var buttons = chooser.querySelectorAll('.add-method-option');

  function switchPanel(method) {
    buttons.forEach(function (b) { b.classList.toggle('active', b.dataset.method === method); });
    panels.forEach(function (p) { p.style.display = 'none'; });

    var panel = document.getElementById('panel-' + method);
    if (panel) panel.style.display = '';

    // Show/hide manual fields
    var manualFields = document.getElementById('manual-fields');
    if (manualFields) {
      manualFields.style.display = method === 'manual' ? '' : 'none';
      numberInput.required = method === 'manual';
    }

    if (method === 'camera') {
      startScanner('scanner-container', 'scan-status');
    } else {
      stopScanner();
    }
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      switchPanel(btn.dataset.method);
    });
  });

  // Auto-start camera
  switchPanel('camera');

  // --- Upload handling ---
  var fileInput = document.getElementById('card-image-input');
  var uploadStatus = document.getElementById('upload-status');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      var file = fileInput.files[0];
      if (!file) return;
      uploadStatus.textContent = 'Scanning image...';

      var html5Qrcode = new Html5Qrcode("upload-reader", {
        formatsToSupport: supportedFormats,
        useBarCodeDetectorIfSupported: true
      });
      html5Qrcode.scanFileV2(file, true)
        .then(function (result) {
          var decodedText = result.decodedText;
          var format = result.result.format
            ? result.result.format.formatName
            : guessFormat(decodedText);
          uploadStatus.textContent = 'Detected: ' + decodedText + ' (' + format + ')';
          applyResult(decodedText, format);
          html5Qrcode.clear();
        })
        .catch(function () {
          uploadStatus.textContent = 'No barcode found in image. Try another photo.';
          html5Qrcode.clear();
        });
    });
  }

  // --- Scanner helpers ---
  var supportedFormats = [
    Html5QrcodeSupportedFormats.QR_CODE,
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.UPC_A,
    Html5QrcodeSupportedFormats.UPC_E,
    Html5QrcodeSupportedFormats.ITF,
    Html5QrcodeSupportedFormats.CODABAR
  ];

  function startScanner(containerId, statusId) {
    stopScanner();
    var status = document.getElementById(statusId);
    status.textContent = 'Starting camera...';

    scanner = new Html5Qrcode(containerId, {
      formatsToSupport: supportedFormats,
      useBarCodeDetectorIfSupported: true
    });
    scanner.start(
      { facingMode: 'environment' },
      {
        fps: 15,
        aspectRatio: 1.777
      },
      function onSuccess(decodedText, decodedResult) {
        var format = decodedResult.result.format.formatName;
        status.textContent = 'Detected: ' + decodedText + ' (' + format + ')';
        applyResult(decodedText, format);
        scanner.stop().catch(function () {});
        scanner = null;
      },
      function onError() {}
    ).then(function () {
      status.textContent = 'Point camera at barcode...';
    }).catch(function (err) {
      status.textContent = 'Camera error: ' + err;
    });
  }

  function stopScanner() {
    if (scanner) {
      scanner.stop().catch(function () {});
      scanner = null;
    }
  }

  function guessFormat(text) {
    if (/^[0-9]{13}$/.test(text)) return 'EAN_13';
    if (/^[0-9]{8}$/.test(text)) return 'EAN_8';
    if (text.length > 20 || /[^A-Z0-9\-. $/+%]/.test(text)) return 'QR_CODE';
    return 'CODE_128';
  }
});
