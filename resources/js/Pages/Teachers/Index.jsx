import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

const TEXT = {
    it: {
        pageTitle: 'Insegnanti',
        listTitle: 'Elenco insegnanti',
        addTeacher: 'Aggiungi insegnante',
        photo: 'Foto',
        name: 'Nome',
        type: 'Tipo',
        email: 'Email',
        phone: 'Telefono',
        taxCode: 'Codice fiscale',
        empty: 'Nessun insegnante.',
        permanent: 'Permanente',
        temporary: 'Temporaneo',
    },
    en: {
        pageTitle: 'Teachers',
        listTitle: 'Teachers list',
        addTeacher: 'Add teacher',
        photo: 'Photo',
        name: 'Name',
        type: 'Type',
        email: 'Email',
        phone: 'Phone',
        taxCode: 'Tax code',
        empty: 'No teachers yet.',
        permanent: 'Permanent',
        temporary: 'Temporary',
    },
    ru: {
        pageTitle: 'Преподаватели',
        listTitle: 'Список преподавателей',
        addTeacher: 'Добавить преподавателя',
        photo: 'Фото',
        name: 'Имя',
        type: 'Тип',
        email: 'Email',
        phone: 'Телефон',
        taxCode: 'Кодиче фискале',
        empty: 'Преподавателей пока нет.',
        permanent: 'Постоянный',
        temporary: 'Временный',
    },
    uk: {
        pageTitle: 'Викладачі',
        listTitle: 'Список викладачів',
        addTeacher: 'Додати викладача',
        photo: 'Фото',
        name: "Ім'я",
        type: 'Тип',
        email: 'Email',
        phone: 'Телефон',
        taxCode: 'Кодіче фіскале',
        empty: 'Викладачів поки що немає.',
        permanent: 'Постійний',
        temporary: 'Тимчасовий',
    },
};

export default function TeachersIndex({ teachers = [] }) {
    const { locale = 'it', auth } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;
    const canWrite = auth?.user?.can_write === true;

    return (
        <AdminLayout title={t.pageTitle}>
            <Head title={t.pageTitle} />

            <div className="space-y-6">
                <section className="app-widget p-4">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-base font-semibold text-slate-900">{t.listTitle}</h2>
                        {canWrite && (
                            <Link
                                href={route('teachers.create')}
                                className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white hover:bg-[#132033]"
                            >
                                {t.addTeacher}
                            </Link>
                        )}
                    </div>
                    <div className="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <Th>{t.photo}</Th>
                                    <Th>{t.name}</Th>
                                    <Th>{t.type}</Th>
                                    <Th>{t.email}</Th>
                                    <Th>{t.phone}</Th>
                                    <Th>{t.taxCode}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {teachers.map((teacher) => (
                                    <tr
                                        key={teacher.id}
                                        className="cursor-pointer hover:bg-slate-50/60"
                                        onClick={() => router.visit(route('teachers.show', teacher.id))}
                                    >
                                        <Td>
                                            {teacher.photo_url ? (
                                                <img
                                                    src={teacher.photo_url}
                                                    alt={teacher.name}
                                                    className="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200"
                                                />
                                            ) : (
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1A2B44]/10 text-xs font-semibold text-[#1A2B44]">
                                                    {getInitials(teacher)}
                                                </div>
                                            )}
                                        </Td>
                                        <Td>{teacher.name}</Td>
                                        <Td>
                                            <TypeBadge type={teacher.type} t={t} />
                                        </Td>
                                        <Td>{teacher.email || '—'}</Td>
                                        <Td>{teacher.phone || '—'}</Td>
                                        <Td>{teacher.tax_code || '—'}</Td>
                                    </tr>
                                ))}
                                {teachers.length === 0 && (
                                    <tr>
                                        <td className="px-4 py-5 text-slate-500" colSpan={6}>{t.empty}</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}

function TypeBadge({ type, t }) {
    const isPermanent = type === 'permanent';
    const label = isPermanent ? t.permanent : t.temporary;

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                isPermanent
                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                    : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
            }`}
        >
            {label}
        </span>
    );
}

function Th({ children }) {
    return <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{children}</th>;
}

function Td({ children }) {
    return <td className="px-4 py-3 text-slate-700">{children}</td>;
}

function getInitials(teacher) {
    const first = (teacher?.first_name || '').trim().charAt(0);
    const last = (teacher?.last_name || '').trim().charAt(0);
    const combined = `${first}${last}`.toUpperCase();

    if (combined) {
        return combined;
    }

    return (teacher?.name || 'TC')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
