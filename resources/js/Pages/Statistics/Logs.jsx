import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const TEXT = {
    it: {
        title: 'Log',
        subtitle: 'Registro delle azioni dei dipendenti nel pannello di amministrazione.',
        filterStaff: 'Dipendente',
        filterStudent: 'Studente',
        allStaff: 'Tutti i dipendenti',
        allStudents: 'Tutti gli studenti',
        apply: 'Applica',
        reset: 'Reimposta',
        colWhen: 'Data',
        colStaff: 'Dipendente',
        colAction: 'Azione',
        colSubject: 'Oggetto',
        colStudent: 'Studente',
        colDetails: 'Dettagli',
        empty: 'Nessuna voce nel registro.',
        page: 'Pagina',
        of: 'di',
        prev: 'Precedente',
        next: 'Successivo',
        actions: {
            created: 'Creato',
            updated: 'Modificato',
            deleted: 'Eliminato',
            login: 'Accesso',
            logout: 'Uscita',
            password_changed: 'Password cambiata',
            checked: 'Connessione verificata',
            check_failed: 'Verifica connessione fallita',
            activated: 'Attivato',
            deactivated: 'Disattivato',
        },
        subjects: {
            customer: 'Studente',
            teacher: 'Insegnante',
            lesson: 'Lezione',
            user: 'Utente',
            role: 'Ruolo',
            profile: 'Profilo',
            auth: 'Autenticazione',
            ai_provider: 'Provider IA',
            settings: 'Impostazioni',
        },
        fields: {
            name: 'Nome',
            email: 'Email',
            phone: 'Telefono',
            address: 'Indirizzo',
            notes: 'Note',
            status: 'Stato',
            role_id: 'Ruolo',
            slug: 'Slug',
            description: 'Descrizione',
            is_admin: 'Admin',
            is_active: 'Attivo',
            menu_keys: 'Voci menu',
            locale: 'Lingua',
            password: 'Password',
            api_key: 'Chiave API',
            model: 'Modello',
        },
    },
    uk: {
        title: 'Логи',
        subtitle: 'Журнал дій співробітників у панелі адміністратора.',
        filterStaff: 'Співробітник',
        filterStudent: 'Студент',
        allStaff: 'Усі співробітники',
        allStudents: 'Усі студенти',
        apply: 'Застосувати',
        reset: 'Скинути',
        colWhen: 'Дата',
        colStaff: 'Співробітник',
        colAction: 'Дія',
        colSubject: "Об'єкт",
        colStudent: 'Студент',
        colDetails: 'Деталі',
        empty: 'Записів поки немає.',
        page: 'Сторінка',
        of: 'з',
        prev: 'Назад',
        next: 'Далі',
        actions: {
            created: 'Створено',
            updated: 'Змінено',
            deleted: 'Видалено',
            login: 'Вхід',
            logout: 'Вихід',
            password_changed: 'Пароль змінено',
            checked: 'З\'єднання перевірено',
            check_failed: 'Помилка перевірки',
            activated: 'Активовано',
            deactivated: 'Деактивовано',
        },
        subjects: {
            customer: 'Студент',
            teacher: 'Викладач',
            lesson: 'Урок',
            user: 'Користувач',
            role: 'Роль',
            profile: 'Профіль',
            auth: 'Автентифікація',
            ai_provider: 'AI провайдер',
            settings: 'Налаштування',
        },
        fields: {
            name: "Ім'я",
            email: 'Email',
            phone: 'Телефон',
            address: 'Адреса',
            notes: 'Нотатки',
            status: 'Статус',
            role_id: 'Роль',
            slug: 'Slug',
            description: 'Опис',
            is_admin: 'Адмін',
            is_active: 'Активна',
            menu_keys: 'Пункти меню',
            locale: 'Мова',
            password: 'Пароль',
            api_key: 'API key',
            model: 'Модель',
        },
    },
    en: {
        title: 'Logs',
        subtitle: 'Activity log of staff actions in the admin panel.',
        filterStaff: 'Staff member',
        filterStudent: 'Student',
        allStaff: 'All staff',
        allStudents: 'All students',
        apply: 'Apply',
        reset: 'Reset',
        colWhen: 'Date',
        colStaff: 'Staff',
        colAction: 'Action',
        colSubject: 'Subject',
        colStudent: 'Student',
        colDetails: 'Details',
        empty: 'No log entries yet.',
        page: 'Page',
        of: 'of',
        prev: 'Previous',
        next: 'Next',
        actions: {
            created: 'Created',
            updated: 'Updated',
            deleted: 'Deleted',
            login: 'Login',
            logout: 'Logout',
            password_changed: 'Password changed',
            checked: 'Connection checked',
            check_failed: 'Connection check failed',
            activated: 'Activated',
            deactivated: 'Deactivated',
        },
        subjects: {
            customer: 'Student',
            teacher: 'Teacher',
            lesson: 'Lesson',
            user: 'User',
            role: 'Role',
            profile: 'Profile',
            auth: 'Authentication',
            ai_provider: 'AI provider',
            settings: 'Settings',
        },
        fields: {
            name: 'Name',
            email: 'Email',
            phone: 'Phone',
            address: 'Address',
            notes: 'Notes',
            status: 'Status',
            role_id: 'Role',
            slug: 'Slug',
            description: 'Description',
            is_admin: 'Admin',
            is_active: 'Active',
            menu_keys: 'Menu items',
            locale: 'Language',
            password: 'Password',
            api_key: 'API key',
            model: 'Model',
        },
    },
    ru: {
        title: 'Логи',
        subtitle: 'Журнал действий сотрудников в админ-панели.',
        filterStaff: 'Сотрудник',
        filterStudent: 'Студент',
        allStaff: 'Все сотрудники',
        allStudents: 'Все студенты',
        apply: 'Применить',
        reset: 'Сбросить',
        colWhen: 'Дата',
        colStaff: 'Сотрудник',
        colAction: 'Действие',
        colSubject: 'Объект',
        colStudent: 'Студент',
        colDetails: 'Детали',
        empty: 'Записей пока нет.',
        page: 'Страница',
        of: 'из',
        prev: 'Назад',
        next: 'Далее',
        actions: {
            created: 'Создано',
            updated: 'Изменено',
            deleted: 'Удалено',
            login: 'Вход',
            logout: 'Выход',
            password_changed: 'Пароль изменён',
            checked: 'Проверка подключения',
            check_failed: 'Ошибка проверки',
            activated: 'Активировано',
            deactivated: 'Деактивировано',
        },
        subjects: {
            customer: 'Студент',
            teacher: 'Преподаватель',
            lesson: 'Урок',
            user: 'Пользователь',
            role: 'Роль',
            profile: 'Профиль',
            auth: 'Аутентификация',
            ai_provider: 'AI провайдер',
            settings: 'Настройки',
        },
        fields: {
            name: 'Имя',
            email: 'Email',
            phone: 'Телефон',
            address: 'Адрес',
            notes: 'Заметки',
            status: 'Статус',
            role_id: 'Роль',
            slug: 'Slug',
            description: 'Описание',
            is_admin: 'Админ',
            is_active: 'Активна',
            menu_keys: 'Пункты меню',
            locale: 'Язык',
            password: 'Пароль',
            api_key: 'API key',
            model: 'Модель',
        },
    },
};

function formatDate(iso, locale) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString(locale === 'uk' ? 'uk-UA' : locale === 'ru' ? 'ru-RU' : locale === 'it' ? 'it-IT' : 'en-GB');
    } catch {
        return iso;
    }
}

function formatValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    if (Array.isArray(value)) return value.join(', ');
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    return String(value);
}

function renderDetails(log, t) {
    const properties = log.properties ?? {};
    const parts = [];

    if (properties.changes && typeof properties.changes === 'object') {
        Object.entries(properties.changes).forEach(([field, change]) => {
            const label = t.fields[field] ?? field;
            if (Array.isArray(change)) {
                parts.push(`${label}: ${formatValue(change[0])} → ${formatValue(change[1])}`);
            } else {
                parts.push(`${label}: ${formatValue(change)}`);
            }
        });
    }

    if (properties.attributes && typeof properties.attributes === 'object') {
        Object.entries(properties.attributes).forEach(([field, value]) => {
            const label = t.fields[field] ?? field;
            parts.push(`${label}: ${formatValue(value)}`);
        });
    }

    if (properties.model) {
        parts.push(`${t.fields.model}: ${properties.model}`);
    }

    if (properties.models_count !== undefined) {
        parts.push(`models: ${properties.models_count}`);
    }

    if (properties.error) {
        parts.push(String(properties.error));
    }

    if (properties.menu_keys) {
        parts.push(`${t.fields.menu_keys}: ${formatValue(properties.menu_keys)}`);
    }

    return parts.length > 0 ? parts.join('; ') : '—';
}

export default function StatisticsLogs({
    logs = { data: [], links: [], meta: {} },
    filters = {},
    staffOptions = [],
    customerOptions = [],
}) {
    const { locale = 'it' } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;

    const [staffId, setStaffId] = useState(String(filters.user_id ?? ''));
    const [customerId, setCustomerId] = useState(String(filters.customer_id ?? ''));

    const applyFilters = () => {
        router.get(route('statistics.logs'), {
            user_id: staffId || undefined,
            customer_id: customerId || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        setStaffId('');
        setCustomerId('');
        router.get(route('statistics.logs'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const entries = logs.data ?? [];
    const currentPage = logs.meta?.current_page ?? 1;
    const lastPage = logs.meta?.last_page ?? 1;

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />

            <div className="space-y-6">
                <p className="text-sm text-slate-600">{t.subtitle}</p>

                <div className="app-widget p-4">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label htmlFor="staff-filter" className="mb-1 block text-sm font-medium text-slate-700">
                                {t.filterStaff}
                            </label>
                            <select
                                id="staff-filter"
                                value={staffId}
                                onChange={(e) => setStaffId(e.target.value)}
                                className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                            >
                                <option value="">{t.allStaff}</option>
                                {staffOptions.map((staff) => (
                                    <option key={staff.id} value={staff.id}>
                                        {staff.name}{staff.email ? ` (${staff.email})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label htmlFor="student-filter" className="mb-1 block text-sm font-medium text-slate-700">
                                {t.filterStudent}
                            </label>
                            <select
                                id="student-filter"
                                value={customerId}
                                onChange={(e) => setCustomerId(e.target.value)}
                                className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                            >
                                <option value="">{t.allStudents}</option>
                                {customerOptions.map((student) => (
                                    <option key={student.id} value={student.id}>
                                        {student.name}{student.email ? ` (${student.email})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex items-end gap-2 md:col-span-2 xl:col-span-2">
                            <button
                                type="button"
                                onClick={applyFilters}
                                className="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700"
                            >
                                {t.apply}
                            </button>
                            <button
                                type="button"
                                onClick={resetFilters}
                                className="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                {t.reset}
                            </button>
                        </div>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-semibold text-slate-600">{t.colWhen}</th>
                                <th className="px-4 py-3 text-left font-semibold text-slate-600">{t.colStaff}</th>
                                <th className="px-4 py-3 text-left font-semibold text-slate-600">{t.colAction}</th>
                                <th className="px-4 py-3 text-left font-semibold text-slate-600">{t.colSubject}</th>
                                <th className="px-4 py-3 text-left font-semibold text-slate-600">{t.colStudent}</th>
                                <th className="px-4 py-3 text-left font-semibold text-slate-600">{t.colDetails}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {entries.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-slate-500">
                                        {t.empty}
                                    </td>
                                </tr>
                            ) : (
                                entries.map((log) => (
                                    <tr key={log.id} className="align-top hover:bg-slate-50/80">
                                        <td className="whitespace-nowrap px-4 py-3 text-slate-700">
                                            {formatDate(log.created_at, locale)}
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            {log.user ? (
                                                <div>
                                                    <div className="font-medium">{log.user.name}</div>
                                                    <div className="text-xs text-slate-500">{log.user.email}</div>
                                                </div>
                                            ) : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            {t.actions[log.action] ?? log.action}
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            <div>{t.subjects[log.subject_type] ?? log.subject_type}</div>
                                            {log.subject_label && (
                                                <div className="text-xs text-slate-500">{log.subject_label}</div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            {log.customer ? (
                                                <div>
                                                    <div className="font-medium">{log.customer.name}</div>
                                                    {log.customer.email && (
                                                        <div className="text-xs text-slate-500">{log.customer.email}</div>
                                                    )}
                                                </div>
                                            ) : '—'}
                                        </td>
                                        <td className="max-w-md px-4 py-3 text-slate-600">
                                            {renderDetails(log, t)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {lastPage > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-slate-600">
                            {t.page} {currentPage} {t.of} {lastPage}
                        </p>
                        <div className="flex gap-2">
                            {(logs.links ?? []).filter((link) => link.url).map((link, index) => (
                                <button
                                    key={`${link.label}-${index}`}
                                    type="button"
                                    disabled={link.active}
                                    onClick={() => router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                    className={`inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-sm ${
                                        link.active
                                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
