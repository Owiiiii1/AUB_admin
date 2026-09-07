import AuthLayout from '@/Layouts/AuthLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { useState } from 'react';

export default function Login({ status, canResetPassword }) {
    const { locale = 'it' } = usePage().props;
    const [showPassword, setShowPassword] = useState(false);

    const translations = {
        it: {
            title: 'Accedi al portale',
            subtitle: "Inserisci le tue credenziali per accedere alla gestione dell'Accademia.",
            email: 'Email',
            emailPlaceholder: 'nome.cognome@accademiaucraina.it',
            password: 'Password',
            forgotPassword: 'Password dimenticata?',
            signingIn: 'Accesso in corso...',
            signIn: 'Accedi',
        },
        ru: {
            title: 'Вход в портал',
            subtitle: 'Введите учетные данные для доступа к управлению академией.',
            email: 'Email',
            emailPlaceholder: 'name@academy.example',
            password: 'Пароль',
            forgotPassword: 'Забыли пароль?',
            signingIn: 'Входим...',
            signIn: 'Войти',
        },
        en: {
            title: 'Portal Login',
            subtitle: 'Enter your credentials to access academy management.',
            email: 'Email address',
            emailPlaceholder: 'name@academy.example',
            password: 'Password',
            forgotPassword: 'Forgot password?',
            signingIn: 'Signing in...',
            signIn: 'Sign in',
        },
        uk: {
            title: 'Вхід до порталу',
            subtitle: 'Введіть облікові дані для доступу до управління академією.',
            email: 'Email',
            emailPlaceholder: 'name@academy.example',
            password: 'Пароль',
            forgotPassword: 'Забули пароль?',
            signingIn: 'Входимо...',
            signIn: 'Увійти',
        },
    };

    const t = translations[locale] ?? translations.it;

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout>
            <Head title={t.title} />
            <div className="space-y-8">
                <div className="space-y-4">
                    <h1 className="font-singo text-center text-4xl font-normal uppercase tracking-[0.05em] text-[#04162e] md:text-left">
                        {t.title}
                    </h1>
                    <p className="text-center text-sm text-[#44474d] md:text-left">{t.subtitle}</p>
                </div>

                {status && (
                    <div className="border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <label htmlFor="email" className="block text-[11px] font-semibold uppercase tracking-[0.12em] text-[#44474d]">
                            {t.email}
                        </label>
                        <div className="relative">
                            <Mail className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#75777e]" />
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="block h-12 w-full border border-[#e5e5e5] bg-white px-12 text-sm text-[#1b1c1c] outline-none transition focus:border-[#04162e] focus:ring-1 focus:ring-[#04162e]"
                                placeholder={t.emailPlaceholder}
                                autoComplete="username"
                                required
                            />
                        </div>
                        {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <label htmlFor="password" className="block text-[11px] font-semibold uppercase tracking-[0.12em] text-[#44474d]">
                                {t.password}
                            </label>
                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-[11px] font-semibold uppercase tracking-[0.08em] text-[#735c00] transition hover:text-[#04162e]"
                                >
                                    {t.forgotPassword}
                                </Link>
                            )}
                        </div>
                        <div className="relative">
                            <Lock className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#75777e]" />
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="block h-12 w-full border border-[#e5e5e5] bg-white px-12 pr-12 text-sm text-[#1b1c1c] outline-none transition focus:border-[#04162e] focus:ring-1 focus:ring-[#04162e]"
                                autoComplete="current-password"
                                required
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((prev) => !prev)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-[#75777e] transition hover:text-[#04162e]"
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                            >
                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                        </div>
                        {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex h-12 w-full items-center justify-center gap-2 bg-[#1A2B44] px-5 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-[#132033] disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span>{processing ? t.signingIn : t.signIn}</span>
                        <ArrowRight className="h-4 w-4" />
                    </button>
                </form>

            </div>
        </AuthLayout>
    );
}
