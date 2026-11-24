import { initializeApp, getApps } from 'firebase/app';
import { getAuth, signInWithEmailAndPassword } from 'firebase/auth';
import { apiUrl } from '../config.js';

const firebaseConfig = {
    apiKey: 'AIzaSyCMqVjbCKVlVfad2P0devEoYPPVg35WOgE',
    authDomain: 'interfaces-web-8cfee.firebaseapp.com',
    projectId: 'interfaces-web-8cfee',
    storageBucket: 'interfaces-web-8cfee.firebasestorage.app',
    messagingSenderId: '236908721908',
    appId: '1:236908721908:web:5c2c58b5cf87c895e650a1',
    measurementId: 'G-GVX5JXFJHG',
};

function getFirebaseAuth() {
    const app = getApps().length ? getApps()[0] : initializeApp(firebaseConfig);
    return getAuth(app);
}

export default {
    emits: ['authenticated'],
    data() {
        return { email: '', password: '', captcha: '', captchaQuestion: 'Cargando…', message: '', loading: false };
    },
    async mounted() {
        try {
            const response = await fetch(apiUrl('captcha.php'));
            if (!response.ok) {
                throw new Error(`Captcha request failed: ${response.status}`);
            }
            const result = await response.json();
            if (!result.pregunta) {
                throw new Error('Captcha response did not include a question');
            }
            this.captchaQuestion = result.pregunta;
        } catch (error) {
            console.error(error);
            this.captchaQuestion = 'No se pudo cargar el captcha. Intente nuevamente.';
        }
    },
    methods: {
        async submitLogin() {
            this.message = '';
            if (!this.email || !this.password || !this.captcha) {
                this.message = 'Complete el correo electrónico, la contraseña y el captcha.';
                return;
            }
            this.loading = true;
            try {
                const captchaResponse = await fetch(apiUrl('validar_captcha.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'captcha=' + encodeURIComponent(this.captcha),
                });
                const captchaResult = await captchaResponse.json();
                if (!captchaResult.correcto) {
                    this.message = 'Captcha incorrecto.';
                    return;
                }

                const userCredential = await signInWithEmailAndPassword(getFirebaseAuth(), this.email, this.password);
                const user = userCredential.user;
                const token = await user.getIdToken();
                const sessionResponse = await fetch(apiUrl('crear_sesion.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: user.email, uid: user.uid, token }),
                });
                const sessionResult = await sessionResponse.json();
                if (sessionResult.correcto === true) {
                    this.$emit('authenticated', sessionResult.rol);
                    window.location.hash = '#consultations';
                } else {
                    this.message = 'No fue posible crear la sesión.';
                }
            } catch (error) {
                console.error(error);
                this.message = 'Correo electrónico o contraseña incorrectos.';
            } finally {
                this.loading = false;
            }
        },
    },
    template: `
        <form @submit.prevent="submitLogin" class="card shadow-sm p-4">
            <h2 class="h4 mb-3">Iniciar sesión para continuar</h2>
            <label class="form-label" for="email">Correo electrónico</label>
            <input id="email" v-model.trim="email" type="email" class="form-control mb-3" required>
            <label class="form-label" for="password">Contraseña</label>
            <input id="password" v-model="password" type="password" class="form-control mb-3" required>
            <label class="form-label" for="captcha">Captcha: {{ captchaQuestion }}</label>
            <input id="captcha" v-model="captcha" type="number" class="form-control mb-3" required>
            <p v-if="message" class="alert alert-danger">{{ message }}</p>
            <button class="btn btn-primary" :disabled="loading">{{ loading ? 'Iniciando sesión…' : 'Iniciar sesión' }}</button>
        </form>
    `,
};
