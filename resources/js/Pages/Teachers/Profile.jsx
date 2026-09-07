import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const TEXT = {
    it: {
        pageTitle: 'Insegnanti',
        createTitle: 'Nuovo insegnante',
        editTitle: 'Profilo insegnante',
        back: 'Indietro',
        save: 'Salva',
        create: 'Crea',
        delete: 'Elimina',
        deleteConfirm: 'Eliminare questo insegnante?',
        password: 'Password',
        confirmDelete: 'Conferma eliminazione',
        saved: 'Modifiche salvate',
        personal: 'Dati anagrafici',
        contacts: 'Contatti',
        details: 'Dettagli',
        firstName: 'Nome',
        lastName: 'Cognome',
        type: 'Tipo',
        permanent: 'Permanente',
        temporary: 'Temporaneo',
        email: 'Email',
        phone: 'Telefono',
        taxCode: 'Codice fiscale',
        description: 'Descrizione',
        photo: 'Foto',
        changePhoto: 'Cambia foto',
        uploadPhoto: 'Carica foto',
    },
    en: {
        pageTitle: 'Teachers',
        createTitle: 'New teacher',
        editTitle: 'Teacher profile',
        back: 'Back',
        save: 'Save',
        create: 'Create',
        delete: 'Delete',
        deleteConfirm: 'Delete this teacher?',
        password: 'Password',
        confirmDelete: 'Confirm deletion',
        saved: 'Changes saved',
        personal: 'Personal details',
        contacts: 'Contacts',
        details: 'Details',
        firstName: 'First name',
        lastName: 'Last name',
        type: 'Type',
        permanent: 'Permanent',
        temporary: 'Temporary',
        email: 'Email',
        phone: 'Phone',
        taxCode: 'Tax code',
        description: 'Description',
        photo: 'Photo',
        changePhoto: 'Change photo',
        uploadPhoto: 'Upload photo',
    },
    ru: {
        pageTitle: 'Преподаватели',
        createTitle: 'Новый преподаватель',
        editTitle: 'Профиль преподавателя',
        back: 'Назад',
        save: 'Сохранить',
        create: 'Создать',
        delete: 'Удалить',
        deleteConfirm: 'Удалить этого преподавателя?',
        password: 'Пароль',
        confirmDelete: 'Подтвердить удаление',
        saved: 'Изменения сохранены',
        personal: 'Личные данные',
        contacts: 'Контакты',
        details: 'Детали',
        firstName: 'Имя',
        lastName: 'Фамилия',
        type: 'Тип',
        permanent: 'Постоянный',
        temporary: 'Временный',
        email: 'Email',
        phone: 'Телефон',
        taxCode: 'Кодиче фискале',
        description: 'Описание',
        photo: 'Фото',
        changePhoto: 'Изменить фото',
        uploadPhoto: 'Загрузить фото',
    },
    uk: {
        pageTitle: 'Викладачі',
        createTitle: 'Новий викладач',
        editTitle: 'Профіль викладача',
        back: 'Назад',
        save: 'Зберегти',
        create: 'Створити',
        delete: 'Видалити',
        deleteConfirm: 'Видалити цього викладача?',
        password: 'Пароль',
        confirmDelete: 'Підтвердити видалення',
        saved: 'Зміни збережено',
        personal: 'Особисті дані',
        contacts: 'Контакти',
        details: 'Деталі',
        firstName: "Ім'я",
        lastName: 'Прізвище',
        type: 'Тип',
        permanent: 'Постійний',
        temporary: 'Тимчасовий',
        email: 'Email',
        phone: 'Телефон',
        taxCode: 'Кодіче фіскале',
        description: 'Опис',
        photo: 'Фото',
        changePhoto: 'Змінити фото',
        uploadPhoto: 'Завантажити фото',
    },
};

const DEFAULT_FORM = {
    type: 'permanent',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    tax_code: '',
    description: '',
    photo: null,
};

export default function TeacherProfile({ mode = 'create', teacher = null }) {
    const { locale = 'it', auth } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;
    const canDelete = auth?.user?.can_delete === true;
    const canWrite = auth?.user?.can_write === true;
    const isEdit = mode === 'edit' && teacher?.id;
    const form = useForm({
        ...DEFAULT_FORM,
        ...mapTeacherToForm(teacher),
    });
    const deleteForm = useForm({ password: '' });
    const [deleteStep, setDeleteStep] = useState(false);
    const [savedNoticeVisible, setSavedNoticeVisible] = useState(false);

    const fullName = [form.data.first_name, form.data.last_name].filter(Boolean).join(' ').trim() || '—';
    const photoPreview = useMemo(() => {
        if (form.data.photo instanceof File) {
            return URL.createObjectURL(form.data.photo);
        }

        return teacher?.photo_path ? `/storage/${teacher.photo_path}` : null;
    }, [form.data.photo, teacher?.photo_path]);

    useEffect(() => {
        return () => {
            if (photoPreview && photoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(photoPreview);
            }
        };
    }, [photoPreview]);

    const submit = (e) => {
        e.preventDefault();
        const sharedOptions = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setSavedNoticeVisible(true);
                window.setTimeout(() => setSavedNoticeVisible(false), 2500);
            },
        };

        if (isEdit) {
            form.transform((data) => ({ ...data, _method: 'patch' }));
            form.post(route('teachers.update', teacher.id), sharedOptions);
            return;
        }

        form.post(route('teachers.store'), sharedOptions);
    };

    const submitDelete = () => {
        if (!isEdit || !teacher?.id) {
            return;
        }

        deleteForm.transform((data) => ({ ...data, _method: 'delete' }));
        deleteForm.post(route('teachers.destroy', teacher.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteStep(false);
                deleteForm.reset();
            },
        });
    };

    return (
        <AdminLayout title={isEdit ? t.editTitle : t.createTitle}>
            <Head title={isEdit ? t.editTitle : t.createTitle} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Link
                        href={route('teachers.index')}
                        className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        {t.back}
                    </Link>
                    {isEdit && canDelete && (
                        <button
                            type="button"
                            onClick={() => setDeleteStep(true)}
                            className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                        >
                            <Trash2 className="h-4 w-4" />
                            {t.delete}
                        </button>
                    )}
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {savedNoticeVisible && (
                        <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {t.saved}
                        </div>
                    )}

                    <fieldset disabled={!canWrite} className={!canWrite ? 'space-y-6 opacity-90' : 'space-y-6'}>
                    <section className="app-widget p-5">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <div className="shrink-0">
                                <div className="h-24 w-24 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                    {photoPreview ? (
                                        <img src={photoPreview} alt={fullName} className="h-full w-full object-cover" />
                                    ) : (
                                        <div className="flex h-full w-full items-center justify-center text-lg font-semibold text-[#1A2B44]">
                                            {getInitials(form.data.first_name, form.data.last_name)}
                                        </div>
                                    )}
                                </div>
                                <input
                                    id="teacher-photo"
                                    type="file"
                                    accept="image/*"
                                    className="hidden"
                                    onChange={(e) => form.setData('photo', e.target.files?.[0] ?? null)}
                                />
                                <label
                                    htmlFor="teacher-photo"
                                    className="mt-2 inline-block cursor-pointer text-sm font-medium text-[#1A2B44] hover:underline"
                                >
                                    {photoPreview ? t.changePhoto : t.uploadPhoto}
                                </label>
                            </div>

                            <div className="grid min-w-0 flex-1 gap-4 sm:grid-cols-2">
                                <Field label={t.firstName} error={form.errors.first_name}>
                                    <input
                                        type="text"
                                        value={form.data.first_name}
                                        onChange={(e) => form.setData('first_name', e.target.value)}
                                        className={inputClass}
                                    />
                                </Field>
                                <Field label={t.lastName} error={form.errors.last_name}>
                                    <input
                                        type="text"
                                        value={form.data.last_name}
                                        onChange={(e) => form.setData('last_name', e.target.value)}
                                        className={inputClass}
                                    />
                                </Field>
                                <Field label={t.type} error={form.errors.type}>
                                    <select
                                        value={form.data.type}
                                        onChange={(e) => form.setData('type', e.target.value)}
                                        className={inputClass}
                                    >
                                        <option value="permanent">{t.permanent}</option>
                                        <option value="temporary">{t.temporary}</option>
                                    </select>
                                </Field>
                            </div>
                        </div>
                    </section>

                    <section className="app-widget p-5">
                        <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{t.contacts}</h3>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={t.email} error={form.errors.email}>
                                <input
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    className={inputClass}
                                />
                            </Field>
                            <Field label={t.phone} error={form.errors.phone}>
                                <input
                                    type="text"
                                    value={form.data.phone}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                    className={inputClass}
                                />
                            </Field>
                            <Field label={t.taxCode} error={form.errors.tax_code}>
                                <input
                                    type="text"
                                    value={form.data.tax_code}
                                    onChange={(e) => form.setData('tax_code', e.target.value)}
                                    className={inputClass}
                                />
                            </Field>
                        </div>
                    </section>

                    <section className="app-widget p-5">
                        <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{t.details}</h3>
                        <Field label={t.description} error={form.errors.description}>
                            <textarea
                                rows={5}
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                className={inputClass}
                            />
                        </Field>
                    </section>

                    {canWrite && (
                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="rounded-lg bg-[#1A2B44] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#132033] disabled:opacity-60"
                            >
                                {isEdit ? t.save : t.create}
                            </button>
                        </div>
                    )}
                    </fieldset>
                </form>
            </div>

            {deleteStep && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                    <div className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-xl">
                        <h3 className="text-base font-semibold text-slate-900">{t.confirmDelete}</h3>
                        <p className="mt-2 text-sm text-slate-600">{t.deleteConfirm}</p>
                        <div className="mt-4">
                            <label className="mb-1 block text-sm font-medium text-slate-700">{t.password}</label>
                            <input
                                type="password"
                                value={deleteForm.data.password}
                                onChange={(e) => deleteForm.setData('password', e.target.value)}
                                className={inputClass}
                            />
                            {deleteForm.errors.password && (
                                <p className="mt-1 text-sm text-red-600">{deleteForm.errors.password}</p>
                            )}
                        </div>
                        <div className="mt-5 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setDeleteStep(false)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.back}
                            </button>
                            <button
                                type="button"
                                onClick={submitDelete}
                                disabled={deleteForm.processing}
                                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.delete}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

const inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-[#1A2B44] focus:outline-none focus:ring-1 focus:ring-[#1A2B44]';

function Field({ label, error, children }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label>
            {children}
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

function mapTeacherToForm(teacher) {
    if (!teacher) {
        return {};
    }

    return {
        type: teacher.type ?? 'permanent',
        first_name: teacher.first_name ?? '',
        last_name: teacher.last_name ?? '',
        email: teacher.email ?? '',
        phone: teacher.phone ?? '',
        tax_code: teacher.tax_code ?? '',
        description: teacher.description ?? '',
        photo: null,
    };
}

function getInitials(firstName, lastName) {
    const first = (firstName || '').trim().charAt(0);
    const last = (lastName || '').trim().charAt(0);
    const combined = `${first}${last}`.toUpperCase();

    return combined || 'TC';
}
