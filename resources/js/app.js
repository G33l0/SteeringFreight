import Alpine from 'alpinejs';
import trackingChat from './chat';

window.Alpine = Alpine;

Alpine.data('trackingChat', trackingChat);

Alpine.start();
