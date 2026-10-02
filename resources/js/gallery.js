import lightGallery from 'lightgallery';
import lgThumbnail from 'lightgallery/plugins/thumbnail';
import lgZoom from 'lightgallery/plugins/zoom';

import 'lightgallery/css/lightgallery-bundle.css';
import '../css/post-gallery.scss';
import '../css/portfolio-gallery.scss';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.post-gallery[data-component="gallery"]').forEach((gallery) => {
        lightGallery(gallery, {
            plugins: [lgThumbnail, lgZoom],
            selector: 'a',
            speed: 400,
            download: false,
            mobileSettings: {
                controls: true,
                showCloseIcon: true,
            },
        });
    });
});
