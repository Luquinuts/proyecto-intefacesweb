import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.13.0/firebase-app.js';
import { getAuth, signInWithEmailAndPassword } from 'https://www.gstatic.com/firebasejs/10.13.0/firebase-auth.js';

const firebaseConfig = {
    apiKey: 'AIzaSyCMqVjbCKVlVfad2P0devEoYPPVg35WOgE',
    authDomain: 'interfaces-web-8cfee.firebaseapp.com',
    projectId: 'interfaces-web-8cfee',
    storageBucket: 'interfaces-web-8cfee.firebasestorage.app',
    messagingSenderId: '236908721908',
    appId: '1:236908721908:web:5c2c58b5cf87c895e650a1',
    measurementId: 'G-GVX5JXFJHG',
};

const auth = getAuth(initializeApp(firebaseConfig));

export default {
    emits: ['authenticated'],
    data() {
        return { email: '', password: '', captcha: '', captchaQuestion: 'Loading…', message: '', loading: false };
    },
    async mounted() {
        const response = await fetch('../backend/captcha.php');
        const result = await response.json();
        this.captchaQuestion = result.pregunta;
    },
    methods: {
        async submitLogin() {
            this.message = '';
            if (!this.email || !this.password || !this.captcha) {
                this.message = 'Complete email, password, and captcha.';
                return;
            }
            this.loading = true;
            try {
                const captchaResponse = await fetch('../backend/validar_captcha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'captcha=' + encodeURIComponent(this.captcha),
                });
                const captchaResult = await captchaResponse.json();
                if (!captchaResult.correcto) {
                    this.message = 'Captcha incorrecto.';
                    return;
                }

                const userCredential = await signInWithEmailAndPassword(auth, this.email, this.password);
                const user = userCredential.user;
                const token = await user.getIdToken();
                const sessionResponse = await fetch('../backend/crear_sesion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: user.email, uid: user.uid, token }),
                });
                const sessionResult = await sessionResponse.json();
                if (sessionResult.correcto === true) {
                    this.$emit('authenticated', sessionResult.rol);
                } else {
                    this.message = 'No fue posible crear la sesión.';
                }
            } catch (error) {
                console.error(error);
                this.message = 'Email o contraseña incorrectos.';
            } finally {
                this.loading = false;
            }
        },
    },
    template: `
        <form @submit.prevent="submitLogin" class="card shadow-sm p-4">
            <h2 class="h4 mb-3">Sign in to continue</h2>
            <label class="form-label" for="email">Email</label>
            <input id="email" v-model.trim="email" type="email" class="form-control mb-3" required>
            <label class="form-label" for="password">Password</label>
            <input id="password" v-model="password" type="password" class="form-control mb-3" required>
            <label class="form-label" for="captcha">Captcha: {{ captchaQuestion }}</label>
            <input id="captcha" v-model="captcha" type="number" class="form-control mb-3" required>
            <p v-if="message" class="alert alert-danger">{{ message }}</p>
            <button class="btn btn-primary" :disabled="loading">{{ loading ? 'Signing in…' : 'Sign in' }}</button>
        </form>
    `,
};
