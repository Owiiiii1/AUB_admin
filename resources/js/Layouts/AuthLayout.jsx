import { usePage } from '@inertiajs/react';

export default function AuthLayout({ children }) {
    const { locale = 'it' } = usePage().props;
    const leftPanelText = {
        it: {
            title: 'Accademia Ucraina di Balletto',
            subtitle:
                "Eccellenza tecnica e tradizione classica nel cuore di Milano. Benvenuti nell'area riservata al personale amministrativo.",
            est: 'Dal 2005',
            city: 'Milano, Italia',
        },
        ru: {
            title: 'Accademia Ucraina di Balletto',
            subtitle:
                'Техническое совершенство и классическая традиция в сердце Милана. Добро пожаловать в закрытую административную зону.',
            est: 'С 2005',
            city: 'Милан, Италия',
        },
        en: {
            title: 'Accademia Ucraina di Balletto',
            subtitle:
                'Technical excellence and classical tradition in the heart of Milan. Welcome to the restricted administrative area.',
            est: 'Est. 2005',
            city: 'Milan, Italy',
        },
        uk: {
            title: 'Accademia Ucraina di Balletto',
            subtitle:
                'Технічна досконалість і класична традиція в серці Мілана. Ласкаво просимо до закритої адміністративної зони.',
            est: 'З 2005',
            city: 'Мілан, Італія',
        },
    };
    const t = leftPanelText[locale] ?? leftPanelText.it;

    return (
        <main className="min-h-screen bg-[#fcfbf7] text-[#1b1c1c]">
            <div className="flex min-h-screen flex-col md:hidden">
                <header className="relative w-full overflow-hidden bg-[#04162e]">
                    <img
                        src="/images/login-mobile-girl.png"
                        alt="Ballerina"
                        className="h-auto w-full object-contain opacity-95"
                    />
                    <div className="absolute inset-0 bg-gradient-to-br from-[#04162e]/45 via-[#10253f]/35 to-[#04162e]/50" />
                    <img
                        src="/images/logo-aub-white.png"
                        alt="AUB Logo"
                        className="absolute left-4 top-3 w-28 object-contain"
                    />
                </header>

                <section className="flex-1 bg-[#fcfbf7] px-6 py-8">
                    <div className="mx-auto w-full max-w-md border border-[#e5e5e5] bg-white p-8 shadow-[0px_4px_20px_rgba(0,0,0,0.04)] sm:p-10">
                        {children}
                    </div>
                </section>
            </div>

            <div className="hidden min-h-screen md:grid lg:grid-cols-2">
                <section className="relative hidden overflow-hidden bg-[#04162e] p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div
                        className="absolute inset-0 bg-cover bg-center opacity-95"
                        style={{ backgroundImage: "url('/images/login-chatgpt-reference.png')" }}
                    />
                    <div className="absolute inset-0 bg-gradient-to-br from-[#04162e]/45 via-[#10253f]/35 to-[#04162e]/50" />

                    <div className="relative z-10">
                        <img
                            src="/images/logo-aub-white.png"
                            alt="AUB Logo"
                            className="h-14 w-auto object-contain"
                        />
                    </div>

                    <div className="relative z-10 max-w-md space-y-4">
                        <h1 className="font-singo text-4xl font-normal uppercase tracking-[0.06em]">{t.title}</h1>
                        <div className="h-1 w-24 bg-[#e9c349]" />
                        <p className="text-base text-white/85">{t.subtitle}</p>
                    </div>
                    <div className="relative z-10 flex gap-8 text-xs uppercase tracking-[0.2em] text-white/60">
                        <span>{t.est}</span>
                        <span>{t.city}</span>
                    </div>
                </section>

                <section className="relative flex items-center justify-center p-6 sm:p-10">
                    <div className="w-full max-w-md space-y-4">
                        <div className="w-full border border-[#e5e5e5] bg-white p-8 shadow-[0px_4px_20px_rgba(0,0,0,0.04)] sm:p-10">
                            {children}
                        </div>
                    </div>
                </section>
            </div>
        </main>
    );
}
