import './bootstrap';

// Fontes empacotadas localmente: a demo funciona mesmo sem internet
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/playfair-display/500.css';
import '@fontsource/playfair-display/600.css';
import '@fontsource/playfair-display/700.css';
import '@fontsource/playfair-display/500-italic.css';

import Alpine from 'alpinejs';
import confetti from 'canvas-confetti';
import { spaceBooking } from './booking';

window.Alpine = Alpine;
window.confetti = confetti;

Alpine.data('spaceBooking', spaceBooking);

Alpine.start();
