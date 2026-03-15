(() => {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    const imageInput = document.getElementById('image');
    const imageUploadInput = document.getElementById('image_upload');
    const previewCard = document.getElementById('image-preview-card');
    const previewImage = document.getElementById('image-preview');
    const eventFormatSelect = document.getElementById('event_format');
    const addressInput = document.getElementById('address');
    const addressFieldWrapper = document.getElementById('address-field-wrapper');
    const initialSlug = slugInput?.dataset.initialSlug ?? '';
    let objectUrl = null;

    if (!titleInput || !slugInput || !imageInput || !imageUploadInput || !previewCard || !previewImage) {
        return;
    }

    const slugify = (value) => value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    const updateSlug = () => {
        const nextSlug = slugify(titleInput.value);
        slugInput.value = nextSlug;
    };

    const clearObjectUrl = () => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    };

    const setPreview = (src) => {
        if (!src) {
            clearObjectUrl();
            previewImage.removeAttribute('src');
            previewCard.classList.add('is-empty');
            return;
        }

        previewImage.src = src;
        previewCard.classList.remove('is-empty');
    };

    previewImage.addEventListener('error', () => {
        if (!imageUploadInput.files || imageUploadInput.files.length === 0) {
            previewImage.removeAttribute('src');
            previewCard.classList.add('is-empty');
        }
    });

    const syncImageInputs = () => {
        const hasUrl = imageInput.value.trim() !== '';
        const hasFile = imageUploadInput.files && imageUploadInput.files.length > 0;

        imageUploadInput.disabled = hasUrl;
        imageInput.disabled = hasFile;
    };

    const updatePreview = () => {
        const file = imageUploadInput.files && imageUploadInput.files[0] ? imageUploadInput.files[0] : null;
        const imageUrl = imageInput.value.trim();

        syncImageInputs();

        if (file) {
            clearObjectUrl();
            objectUrl = URL.createObjectURL(file);
            setPreview(objectUrl);
            return;
        }

        clearObjectUrl();
        setPreview(imageUrl);
    };

    const syncEventFormat = () => {
        if (!eventFormatSelect || !addressInput || !addressFieldWrapper) {
            return;
        }

        const selectedFormat = eventFormatSelect.value;
        const addressRequired = selectedFormat === 'physical' || selectedFormat === 'hybrid';

        addressInput.required = addressRequired;
        addressFieldWrapper.classList.toggle('is-optional', !addressRequired);
    };

    titleInput.addEventListener('input', updateSlug);
    imageInput.addEventListener('input', updatePreview);
    imageInput.addEventListener('change', updatePreview);
    imageInput.addEventListener('blur', updatePreview);
    imageUploadInput.addEventListener('change', updatePreview);

    if (eventFormatSelect) {
        eventFormatSelect.addEventListener('change', syncEventFormat);
    }

    if (initialSlug) {
        slugInput.value = initialSlug;
    } else {
        updateSlug();
    }

    syncEventFormat();
    updatePreview();
})();
