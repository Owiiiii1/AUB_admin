import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { locale = 'it' } = usePage().props;
    const text = {
        it: {
            title: 'Home',
            body: 'Questa è una pagina home segnaposto. Accesso effettuato con successo.',
        },
        en: {
            title: 'Home',
            body: 'This is a placeholder home page. Login is successful.',
        },
        ru: {
            title: 'Главная',
            body: 'Это заглушка главной страницы. Вход выполнен успешно.',
        },
        uk: {
            title: 'Головна',
            body: 'Це заглушка головної сторінки. Вхід виконано успішно.',
        },
    };
    const t = text[locale] ?? text.it;

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="app-widget p-4">
                <p className="text-sm text-slate-700">{t.body}</p>
            </div>
        </AdminLayout>
    );
}
