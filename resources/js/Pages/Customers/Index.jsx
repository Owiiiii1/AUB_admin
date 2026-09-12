import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';

const TEXT = {
    it: {
        pageTitle: 'Studenti',
        listTitle: 'Elenco studenti',
        addStudent: 'Aggiungi studente',
        photo: 'Foto',
        name: 'Nome',
        age: 'Eta',
        email: 'Email',
        phone: 'Telefono',
        message: 'Messaggio',
        status: 'Stato',
        empty: 'Nessuno studente.',
        active: 'attivo',
        inactive: 'inattivo',
    },
    en: {
        pageTitle: 'Students',
        listTitle: 'Students list',
        addStudent: 'Add student',
        photo: 'Photo',
        name: 'Name',
        age: 'Age',
        email: 'Email',
        phone: 'Phone',
        message: 'Message',
        status: 'Status',
        empty: 'No students yet.',
        active: 'active',
        inactive: 'inactive',
    },
    ru: {
        pageTitle: 'Студенты',
        listTitle: 'Список студентов',
        addStudent: 'Добавить студента',
        photo: 'Фото',
        name: 'Имя',
        age: 'Возраст',
        email: 'Email',
        phone: 'Телефон',
        message: 'Сообщение',
        status: 'Статус',
        empty: 'Студентов пока нет.',
        active: 'активен',
        inactive: 'неактивен',
    },
    uk: {
        pageTitle: 'Студенти',
        listTitle: 'Список студентів',
        addStudent: 'Додати студента',
        photo: 'Фото',
        name: "Ім'я",
        age: 'Вік',
        email: 'Email',
        phone: 'Телефон',
        message: 'Повідомлення',
        status: 'Статус',
        empty: 'Студентів поки що немає.',
        active: 'активний',
        inactive: 'неактивний',
    },
};

export default function CustomersIndex({ customers = [] }) {
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
                            <Link href={route('customers.create')} className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white hover:bg-[#132033]">
                                {t.addStudent}
                            </Link>
                        )}
                    </div>
                    <div className="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <Th>{t.photo}</Th><Th>{t.name}</Th><Th>{t.age}</Th><Th>{t.email}</Th><Th>{t.phone}</Th><Th>{t.message}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {customers.map((customer) => (
                                    <tr
                                        key={customer.id}
                                        className="cursor-pointer hover:bg-slate-50/60"
                                        onClick={() => router.visit(route('customers.show', customer.id))}
                                    >
                                        <Td>
                                            {customer.student_photo_url ? (
                                                <img
                                                    src={customer.student_photo_url}
                                                    alt={[customer.first_name, customer.last_name].filter(Boolean).join(' ') || customer.name || 'Student'}
                                                    className="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200"
                                                />
                                            ) : (
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1A2B44]/10 text-xs font-semibold text-[#1A2B44]">
                                                    {getInitials(customer)}
                                                </div>
                                            )}
                                        </Td>
                                        <Td>{[customer.first_name, customer.last_name].filter(Boolean).join(' ') || customer.name}</Td>
                                        <Td>{calculateAge(customer.birth_date)}</Td>
                                        <Td>{customer.student_email || customer.email || '—'}</Td>
                                        <Td>{customer.student_phone || customer.phone || '—'}</Td>
                                        <Td>
                                            <button
                                                type="button"
                                                disabled
                                                title={t.message}
                                                className="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-md border border-slate-200 text-slate-400 opacity-60"
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                <MessageSquare className="h-4 w-4" />
                                            </button>
                                        </Td>
                                    </tr>
                                ))}
                                {customers.length === 0 && (
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

function Th({ children }) {
    return <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{children}</th>;
}

function Td({ children }) {
    return <td className="px-4 py-3 text-slate-700">{children}</td>;
}

function getInitials(customer) {
    const first = (customer?.first_name || '').trim().charAt(0);
    const last = (customer?.last_name || '').trim().charAt(0);
    const combined = `${first}${last}`.toUpperCase();
    if (combined) {
        return combined;
    }

    return (customer?.name || 'ST')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

function calculateAge(dateString) {
    if (!dateString) {
        return '—';
    }

    const birthDate = new Date(dateString);
    if (Number.isNaN(birthDate.getTime())) {
        return '—';
    }

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age -= 1;
    }

    return age >= 0 ? age : '—';
}
