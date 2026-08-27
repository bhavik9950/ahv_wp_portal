import './bootstrap';

// SPA-style navigation: fetch pages in the background and swap the <body>
// instead of a full document reload. Must load before Alpine.
import '@hotwired/turbo';
import 'alpine-turbo-drive-adapter';
import './turbo-setup';

import './alerts';
import './datatables';
import './confirm-forms';
import './form-loading';
import './row-links';
import './trend-chart';
import './template-preview';
import './nav-active';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
