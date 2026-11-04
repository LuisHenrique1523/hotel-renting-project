import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/components/logo.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/saade/filament-fullcalendar/resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                display: ['"Playfair Display"', 'serif'],
            },
            colors: {
                night: { 50: '#F2F5F9', 100: '#E1E7EF', 200: '#C3CFDF', 300: '#93A6C2', 400: '#5E7699', 500: '#3D5679', 600: '#2B3F5C', 700: '#1F3049', 800: '#16243A', 900: '#0F1E33', 950: '#091424' },
                sand: { 50: '#FCFAF6', 100: '#F6F0E6', 200: '#E9DCC9' },
                gold: { 100: '#F4E8C9', 300: '#DCBA74', 400: '#D1AB64', 500: '#C8A15A', 600: '#A9823F', 700: '#876532' },
            },
        },
    },
}
