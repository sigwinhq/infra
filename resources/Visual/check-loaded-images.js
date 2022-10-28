window.addEventListener('load', () => {
    const images = Array.from(document.querySelectorAll('img')).filter(img => img.checkVisibility());
    const imageCount = images.length;
    let loadedImageCount = 0;

    if (!imageCount) {
        triggerEventComplete();
    } else {
        images.forEach((image, index) => {
            if (image.complete) {
                countLoaded();
            } else {
                image.onload = countLoaded;
            }
        });
    }

    function countLoaded() {
        loadedImageCount++;
        if (imageCount === loadedImageCount) {
            triggerEventComplete();
        }
    }

    function triggerEventComplete() {
        console.log('BackstopVisibleImagesLoaded');
    }
});
