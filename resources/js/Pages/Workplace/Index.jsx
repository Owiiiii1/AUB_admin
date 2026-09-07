import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, usePage } from '@inertiajs/react';

const menuLabels = {
    it: {
        dashboard: 'Home',
        students: 'Studenti',
        statistics: 'Statistiche',
    },
    en: {
        dashboard: 'Home',
        students: 'Students',
        statistics: 'Statistics',
    },
    ru: {
        dashboard: 'Главная',
        students: 'Студенты',
        statistics: 'Статистика',
    },
    uk: {
        dashboard: 'Головна',
        students: 'Студенти',
        statistics: 'Статистика',
    },
};

export default function WorkplaceIndex({ menuItems = [], roleName = '' }) {
    const { locale = 'it' } = usePage().props;
    const labels = menuLabels[locale] ?? menuLabels.it;
    return (
        <AdminLayout title="Workplace">
            <Head title="Workplace" />

            <div className="space-y-4">
                <div>
                    <h2 className="text-lg font-semibold text-slate-900">Your workplace</h2>
                    <p className="mt-1 text-sm text-slate-600">
                        {roleName ? `Role: ${roleName}` : 'Limited access workspace'}
                    </p>
                </div>

                {menuItems.length === 0 ? (
                    <p className="text-sm text-slate-500">No screens are assigned to your role yet.</p>
                ) : (
                    <ul className="grid gap-3 sm:grid-cols-2">
                        {menuItems.map((item) => (
                            <li key={item.menu_key}>
                                <Link
                                    href={route(item.route_name)}
                                    className="block rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 transition hover:border-indigo-300 hover:bg-indigo-50"
                                >
                                    {labels[item.menu_key] ?? item.menu_key}
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminLayout>
    );
}
