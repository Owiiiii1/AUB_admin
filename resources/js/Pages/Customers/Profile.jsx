import AdminLayout from '@/Layouts/AdminLayout';
import AccountPanel from '@/Components/AccountPanel';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const TEXT = {
    it: {
        listTitle: 'Profilo Studente',
        back: 'Indietro',
        backToCoursesGroups: 'Corsi e gruppi',
        create: 'Crea studente',
        save: 'Salva modifiche',
        subtitle: 'Profilo completo dello studente',
        personal: "Dati anagrafici dell'allievo/a",
        contacts: "Contatti e residenza dell'allievo/a",
        course: 'Informazioni sul corso e iscrizione',
        parents: 'Genitori / Tutori legali',
        documents: 'Documenti e file',
        notesSection: 'Note',
        firstName: 'Nome',
        lastName: 'Cognome',
        gender: 'Sesso',
        male: 'Maschio',
        female: 'Femmina',
        other: 'Altro',
        taxCode: 'Codice fiscale',
        birthDate: 'Data di nascita',
        birthPlace: 'Luogo di nascita',
        address: 'Indirizzo di residenza',
        cityProvince: 'Citta e Provincia',
        postalCode: 'CAP',
        studentEmail: "Email dell'allievo/a",
        studentPhone: "Telefono dell'allievo/a",
        parentPhone: 'Telefono genitori/tutori',
        parentEmail: 'Email genitori/tutori',
        courseName: 'Corso (a.a. 2026/27)',
        otherCourses: 'Iscritto anche a',
        existingStudent: 'Gia allievo A.U.B.',
        formDate: 'Data compilazione modulo',
        medicalExpiry: 'Scadenza certificato medico',
        fatherTitle: 'Papa',
        motherTitle: 'Mamma',
        phone: 'Telefono',
        email: 'Email',
        notes: 'Note',
        studentNotes: "Note sull'allievo/a",
        studentPhoto: "Fototessera dell'allievo",
        parentDocument: "Documento d'identita genitore/tutore",
        regulationForm: 'Modulo Regolamento generale',
        minorEntryForm: 'Modulo Entrata/Uscita minorenni',
        rightsForm: 'Modulo Liberatoria cessione diritti',
        currentFile: 'File attuale',
        noFile: 'Nessun file caricato',
        onlyRequired: 'Obbligatori solo Nome e Cognome.',
        ageLabel: 'Eta',
        courseShort: 'Corso',
        noCourse: 'non indicato',
        openInfo: 'Apri dati',
        close: 'Chiudi',
        savedSuccess: 'Dati salvati con successo.',
        deleteBlockTitle: 'Eliminazione studente',
        currentPassword: 'Password corrente',
        deleteStudent: 'Elimina studente',
        deleteConfirm: 'Confermi eliminazione dello studente? Questa azione e irreversibile.',
        confirmAction: 'Conferma',
        cancel: 'Annulla',
        deletePasswordStep: 'Inserisci la password corrente per confermare eliminazione.',
        accountTitle: 'Account',
        accountNotCreated: 'Account non creato',
        accountLinked: 'Account collegato',
        accountActive: 'Attivo',
        accountInactive: 'Disattivato',
        createAccount: 'Crea account',
        linkAccount: 'Collega account esistente',
        unlinkAccount: 'Scollega',
        disableAccount: 'Disattiva account',
        fieldName: 'Nome',
        fieldEmail: 'Email',
        fieldPassword: 'Password temporanea',
        fieldIsActive: 'Account attivo',
        noLinkableUsers: 'Nessun account compatibile disponibile.',
        saveParentFirst: 'Salva prima i dati del genitore, poi potrai creare l\'account.',
    },
    ru: {
        listTitle: 'Профиль студента',
        back: 'Назад',
        backToCoursesGroups: 'Курсы и группы',
        create: 'Создать студента',
        save: 'Сохранить изменения',
        subtitle: 'Полный профиль студента',
        personal: 'Анкетные данные ученика',
        contacts: 'Контакты и адрес ученика',
        course: 'Информация о курсе и зачислении',
        parents: 'Родители / опекуны',
        documents: 'Документы и файлы',
        notesSection: 'Заметки',
        firstName: 'Имя',
        lastName: 'Фамилия',
        gender: 'Пол',
        male: 'Мужской',
        female: 'Женский',
        other: 'Другой',
        taxCode: 'Налоговый код',
        birthDate: 'Дата рождения',
        birthPlace: 'Место рождения',
        address: 'Адрес проживания',
        cityProvince: 'Город и провинция',
        postalCode: 'Индекс',
        studentEmail: 'Email ученика',
        studentPhone: 'Телефон ученика',
        parentPhone: 'Телефон родителей/опекунов',
        parentEmail: 'Email родителей/опекунов',
        courseName: 'Курс (2026/27)',
        otherCourses: 'Также записан на',
        existingStudent: 'Уже ученик A.U.B.',
        formDate: 'Дата заполнения формы',
        medicalExpiry: 'Срок мед. справки',
        fatherTitle: 'Папа',
        motherTitle: 'Мама',
        phone: 'Телефон',
        email: 'Email',
        notes: 'Заметки',
        studentNotes: 'Заметки по ученику',
        studentPhoto: 'Фото ученика',
        parentDocument: 'Документ родителя/опекуна',
        regulationForm: 'Форма общего регламента',
        minorEntryForm: 'Форма входа/выхода несовершеннолетних',
        rightsForm: 'Форма передачи прав',
        currentFile: 'Текущий файл',
        noFile: 'Файл не загружен',
        onlyRequired: 'Обязательны только Имя и Фамилия.',
        ageLabel: 'Возраст',
        courseShort: 'Курс',
        noCourse: 'не указан',
        openInfo: 'Открыть данные',
        close: 'Закрыть',
        savedSuccess: 'Данные успешно сохранены.',
        deleteBlockTitle: 'Удаление студента',
        currentPassword: 'Текущий пароль',
        deleteStudent: 'Удалить студента',
        deleteConfirm: 'Подтвердите удаление студента. Это действие необратимо.',
        confirmAction: 'Подтвердить',
        cancel: 'Отмена',
        deletePasswordStep: 'Введите текущий пароль для подтверждения удаления.',
        accountTitle: 'Аккаунт',
        accountNotCreated: 'Аккаунт не создан',
        accountLinked: 'Связанный аккаунт',
        accountActive: 'Активен',
        accountInactive: 'Отключён',
        createAccount: 'Создать аккаунт',
        linkAccount: 'Привязать существующий',
        unlinkAccount: 'Отвязать',
        disableAccount: 'Отключить аккаунт',
        fieldName: 'Имя',
        fieldEmail: 'Email',
        fieldPassword: 'Временный пароль',
        fieldIsActive: 'Аккаунт активен',
        noLinkableUsers: 'Нет совместимых аккаунтов.',
        saveParentFirst: 'Сначала сохраните данные родителя, затем можно создать аккаунт.',
    },
    en: {
        listTitle: 'Student Profile',
        back: 'Back',
        backToCoursesGroups: 'Courses and groups',
        create: 'Create student',
        save: 'Save changes',
        subtitle: 'Full student profile',
        personal: 'Student personal data',
        contacts: 'Student contacts and residence',
        course: 'Course and enrollment information',
        parents: 'Parents / legal guardians',
        documents: 'Documents and files',
        notesSection: 'Notes',
        firstName: 'First name',
        lastName: 'Last name',
        gender: 'Gender',
        male: 'Male',
        female: 'Female',
        other: 'Other',
        taxCode: 'Tax code',
        birthDate: 'Birth date',
        birthPlace: 'Birth place',
        address: 'Residence address',
        cityProvince: 'City and Province',
        postalCode: 'Postal code',
        studentEmail: 'Student email',
        studentPhone: 'Student phone',
        parentPhone: 'Parent/guardian phone',
        parentEmail: 'Parent/guardian email',
        courseName: 'Course (a.y. 2026/27)',
        otherCourses: 'Also enrolled in',
        existingStudent: 'Already A.U.B. student',
        formDate: 'Form date',
        medicalExpiry: 'Medical certificate expiry',
        fatherTitle: 'Father',
        motherTitle: 'Mother',
        phone: 'Phone',
        email: 'Email',
        notes: 'Notes',
        studentNotes: 'Student notes',
        studentPhoto: 'Student photo',
        parentDocument: 'Parent/guardian ID',
        regulationForm: 'General regulation form',
        minorEntryForm: 'Minor entry/exit form',
        rightsForm: 'Rights release form',
        currentFile: 'Current file',
        noFile: 'No file uploaded',
        onlyRequired: 'Only First name and Last name are required.',
        ageLabel: 'Age',
        courseShort: 'Course',
        noCourse: 'not set',
        openInfo: 'Open details',
        close: 'Close',
        savedSuccess: 'Data saved successfully.',
        deleteBlockTitle: 'Delete student',
        currentPassword: 'Current password',
        deleteStudent: 'Delete student',
        deleteConfirm: 'Confirm student deletion. This action cannot be undone.',
        confirmAction: 'Confirm',
        cancel: 'Cancel',
        deletePasswordStep: 'Enter current password to confirm deletion.',
        accountTitle: 'Account',
        accountNotCreated: 'Account not created',
        accountLinked: 'Linked account',
        accountActive: 'Active',
        accountInactive: 'Disabled',
        createAccount: 'Create account',
        linkAccount: 'Link existing account',
        unlinkAccount: 'Unlink',
        disableAccount: 'Disable account',
        fieldName: 'Name',
        fieldEmail: 'Email',
        fieldPassword: 'Temporary password',
        fieldIsActive: 'Account active',
        noLinkableUsers: 'No compatible accounts available.',
        saveParentFirst: 'Save the parent details first, then you can create an account.',
    },
    uk: {
        listTitle: 'Профіль студента',
        back: 'Назад',
        backToCoursesGroups: 'Курси і групи',
        create: 'Створити студента',
        save: 'Зберегти зміни',
        subtitle: 'Повний профіль студента',
        personal: 'Анкетні дані студента',
        contacts: 'Контакти та адреса студента',
        course: 'Інформація про курс і зарахування',
        parents: 'Батьки / опікуни',
        documents: 'Документи та файли',
        notesSection: 'Нотатки',
        firstName: "Ім'я",
        lastName: 'Прізвище',
        gender: 'Стать',
        male: 'Чоловіча',
        female: 'Жіноча',
        other: 'Інша',
        taxCode: 'Податковий код',
        birthDate: 'Дата народження',
        birthPlace: 'Місце народження',
        address: 'Адреса проживання',
        cityProvince: 'Місто і провінція',
        postalCode: 'Індекс',
        studentEmail: 'Email студента',
        studentPhone: 'Телефон студента',
        parentPhone: 'Телефон батьків/опікунів',
        parentEmail: 'Email батьків/опікунів',
        courseName: 'Курс (2026/27)',
        otherCourses: 'Також записаний на',
        existingStudent: 'Вже учень A.U.B.',
        formDate: 'Дата заповнення форми',
        medicalExpiry: 'Термін медичної довідки',
        fatherTitle: 'Тато',
        motherTitle: 'Мама',
        phone: 'Телефон',
        email: 'Email',
        notes: 'Нотатки',
        studentNotes: 'Нотатки по студенту',
        studentPhoto: 'Фото студента',
        parentDocument: 'Документ батька/опікуна',
        regulationForm: 'Форма загального регламенту',
        minorEntryForm: 'Форма входу/виходу неповнолітніх',
        rightsForm: 'Форма передачі прав',
        currentFile: 'Поточний файл',
        noFile: 'Файл не завантажено',
        onlyRequired: "Обов'язкові лише Ім'я та Прізвище.",
        ageLabel: 'Вік',
        courseShort: 'Курс',
        noCourse: 'не вказано',
        openInfo: 'Відкрити дані',
        close: 'Закрити',
        savedSuccess: 'Дані успішно збережено.',
        deleteBlockTitle: 'Видалення студента',
        currentPassword: 'Поточний пароль',
        deleteStudent: 'Видалити студента',
        deleteConfirm: 'Підтвердьте видалення студента. Цю дію неможливо скасувати.',
        confirmAction: 'Підтвердити',
        cancel: 'Скасувати',
        deletePasswordStep: 'Введіть поточний пароль для підтвердження видалення.',
        accountTitle: 'Акаунт',
        accountNotCreated: 'Акаунт не створено',
        accountLinked: 'Пов’язаний акаунт',
        accountActive: 'Активний',
        accountInactive: 'Вимкнений',
        createAccount: 'Створити акаунт',
        linkAccount: 'Прив’язати наявний',
        unlinkAccount: 'Відв’язати',
        disableAccount: 'Вимкнути акаунт',
        fieldName: "Ім'я",
        fieldEmail: 'Email',
        fieldPassword: 'Тимчасовий пароль',
        fieldIsActive: 'Акаунт активний',
        noLinkableUsers: 'Немає сумісних акаунтів.',
        saveParentFirst: 'Спочатку збережіть дані батька/матері, потім можна створити акаунт.',
    },
};

const DEFAULT_FORM = {
    first_name: '',
    last_name: '',
    gender: '',
    tax_code: '',
    birth_date: '',
    birth_place: '',
    residence_address: '',
    residence_city_province: '',
    residence_postal_code: '',
    student_email: '',
    student_phone: '',
    parent_phone: '',
    parent_email: '',
    course_aa_2026_27: '',
    other_courses: '',
    is_existing_student: false,
    form_filled_at: '',
    medical_certificate_expiry: '',
    student_photo: null,
    parent_id_document: null,
    general_regulation_form: null,
    minor_entry_exit_form: null,
    rights_release_form: null,
    father_first_name: '',
    father_last_name: '',
    father_phone: '',
    father_email: '',
    father_notes: '',
    mother_first_name: '',
    mother_last_name: '',
    mother_phone: '',
    mother_email: '',
    mother_notes: '',
    student_notes: '',
};

export default function CustomerProfile({
    mode = 'create',
    customer = null,
    returnTo = null,
    linkableStudentUsers = [],
    linkableParentUsers = [],
}) {
    const { locale = 'it', auth } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;
    const canDelete = auth?.user?.can_delete === true;
    const canWrite = auth?.user?.can_write === true;
    const isEdit = mode === 'edit' && customer?.id;
    const backHref = returnTo?.route_name
        ? route(returnTo.route_name, returnTo.route_params ?? {})
        : route('customers.index');
    const backLabel = returnTo ? t.backToCoursesGroups : t.back;
    const form = useForm({
        ...DEFAULT_FORM,
        ...mapCustomerToForm(customer),
    });
    const deleteForm = useForm({
        password: '',
    });
    const [parentModal, setParentModal] = useState(null);
    const [deleteStep, setDeleteStep] = useState(null);
    const [savedNoticeVisible, setSavedNoticeVisible] = useState(false);

    const fullName = [form.data.first_name, form.data.last_name].filter(Boolean).join(' ').trim() || '—';
    const photoPreview = useMemo(() => {
        if (form.data.student_photo instanceof File) {
            return URL.createObjectURL(form.data.student_photo);
        }

        return customer?.student_photo_path ? `/storage/${customer.student_photo_path}` : null;
    }, [form.data.student_photo, customer?.student_photo_path]);
    const age = calculateAge(form.data.birth_date);
    const quickCourse = form.data.course_aa_2026_27 || t.noCourse;

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
            form.transform((data) => ({
                ...data,
                _method: 'patch',
            }));
            form.post(route('customers.update', customer.id), sharedOptions);
            return;
        }
        form.transform((data) => data);
        form.post(route('customers.store'), sharedOptions);
    };

    const submitDelete = () => {
        if (!isEdit || !customer?.id) {
            return;
        }

        deleteForm.transform((data) => ({
            ...data,
            _method: 'delete',
        }));
        deleteForm.post(route('customers.destroy', customer.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteStep(null);
                deleteForm.reset();
            },
        });
    };

    return (
        <AdminLayout title={t.listTitle}>
            <Head title={t.listTitle} />

            <div className="space-y-6">
                <form onSubmit={submit} className="space-y-6">
                    {savedNoticeVisible && (
                        <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {t.savedSuccess}
                        </div>
                    )}

                    <div className="flex items-center justify-between">
                        <Link href={backHref} className="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-[#1A2B44] hover:bg-slate-50">
                            <span aria-hidden>←</span>
                            {backLabel}
                        </Link>
                        {canWrite && (
                            <button type="submit" className="rounded-md bg-[#1A2B44] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#132033] disabled:opacity-60" disabled={form.processing}>
                                {isEdit ? t.save : t.create}
                            </button>
                        )}
                    </div>

                    <fieldset disabled={!canWrite} className={!canWrite ? 'space-y-6 opacity-90' : 'space-y-6'}>
                        <div className="flex flex-col gap-6 md:flex-row md:items-start">
                            <div className="relative h-32 w-32 overflow-hidden rounded-lg border border-[#E5E5E5] bg-[#f6f3f2]">
                                {photoPreview ? (
                                    <img src={photoPreview} alt={fullName} className="h-full w-full object-cover" />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center text-3xl font-semibold text-[#1A2B44]">
                                        {getInitials(form.data.first_name, form.data.last_name)}
                                    </div>
                                )}
                                <input
                                    id="student-photo-top"
                                    type="file"
                                    accept="image/*"
                                    className="hidden"
                                    onChange={(e) => form.setData('student_photo', e.target.files?.[0] ?? null)}
                                />
                                <label
                                    htmlFor="student-photo-top"
                                    title={t.studentPhoto}
                                    className="absolute bottom-2 right-2 inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-[#1A2B44] text-white shadow-md hover:bg-[#132033]"
                                >
                                    <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                </label>
                            </div>
                            <div className="flex-1">
                                <h1 className="font-singo text-3xl text-[#04162e]">{fullName}</h1>
                                <p className="mt-1 text-sm text-[#44474d]">
                                    {t.ageLabel}: {age} - {t.courseShort}: {quickCourse}
                                </p>
                            </div>
                        </div>

                    <Section title={t.personal}>
                        <Grid>
                            <Field t={t} form={form} name="first_name" label={t.firstName} required />
                            <Field t={t} form={form} name="last_name" label={t.lastName} required />
                            <SelectField
                                t={t}
                                form={form}
                                name="gender"
                                label={t.gender}
                                options={[
                                    { value: '', label: '—' },
                                    { value: 'male', label: t.male },
                                    { value: 'female', label: t.female },
                                    { value: 'other', label: t.other },
                                ]}
                            />
                            <Field t={t} form={form} name="tax_code" label={t.taxCode} />
                            <Field t={t} form={form} name="birth_date" label={t.birthDate} type="date" />
                            <Field t={t} form={form} name="birth_place" label={t.birthPlace} />
                        </Grid>
                    </Section>

                    <Section title={t.contacts}>
                        <Grid>
                            <Field t={t} form={form} name="residence_address" label={t.address} />
                            <Field t={t} form={form} name="residence_city_province" label={t.cityProvince} />
                            <Field t={t} form={form} name="residence_postal_code" label={t.postalCode} />
                            <Field t={t} form={form} name="student_email" label={t.studentEmail} type="email" />
                            <Field t={t} form={form} name="student_phone" label={t.studentPhone} />
                            <Field t={t} form={form} name="parent_phone" label={t.parentPhone} />
                            <Field t={t} form={form} name="parent_email" label={t.parentEmail} type="email" />
                        </Grid>
                    </Section>

                    <Section title={t.course}>
                        <Grid>
                            <Field t={t} form={form} name="course_aa_2026_27" label={t.courseName} />
                            <Field t={t} form={form} name="other_courses" label={t.otherCourses} />
                            <Field t={t} form={form} name="form_filled_at" label={t.formDate} type="date" />
                            <Field t={t} form={form} name="medical_certificate_expiry" label={t.medicalExpiry} type="date" />
                            <div className="md:col-span-2">
                                <label className="inline-flex items-center gap-2 text-sm text-[#1b1c1c]">
                                    <input
                                        type="checkbox"
                                        checked={!!form.data.is_existing_student}
                                        onChange={(e) => form.setData('is_existing_student', e.target.checked)}
                                        className="rounded border-[#c5c6ce]"
                                    />
                                    {t.existingStudent}
                                </label>
                            </div>
                        </Grid>
                    </Section>

                    <Section title={t.parents}>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <ParentCardButton
                                title={t.fatherTitle}
                                name={[form.data.father_first_name, form.data.father_last_name].filter(Boolean).join(' ') || '—'}
                                subtitle={form.data.father_phone || form.data.father_email || '—'}
                                buttonText={t.openInfo}
                                onClick={() => setParentModal('father')}
                            />
                            <ParentCardButton
                                title={t.motherTitle}
                                name={[form.data.mother_first_name, form.data.mother_last_name].filter(Boolean).join(' ') || '—'}
                                subtitle={form.data.mother_phone || form.data.mother_email || '—'}
                                buttonText={t.openInfo}
                                onClick={() => setParentModal('mother')}
                            />
                        </div>
                    </Section>

                    <Section title={t.documents}>
                        <Grid>
                            <FileField t={t} form={form} field="parent_id_document" label={t.parentDocument} existingPath={customer?.parent_id_document_path} />
                            <FileField t={t} form={form} field="general_regulation_form" label={t.regulationForm} existingPath={customer?.general_regulation_form_path} />
                            <FileField t={t} form={form} field="minor_entry_exit_form" label={t.minorEntryForm} existingPath={customer?.minor_entry_exit_form_path} />
                            <FileField t={t} form={form} field="rights_release_form" label={t.rightsForm} existingPath={customer?.rights_release_form_path} />
                        </Grid>
                    </Section>

                    <Section title={t.notesSection}>
                        <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-[#44474d]">{t.studentNotes}</label>
                        <textarea
                            rows={4}
                            value={form.data.student_notes}
                            onChange={(e) => form.setData('student_notes', e.target.value)}
                            className="w-full rounded-md border border-[#E5E5E5] bg-white px-3 py-2 text-sm text-[#1b1c1c] focus:border-[#1A2B44] focus:outline-none"
                        />
                        {form.errors.student_notes && <p className="mt-1 text-xs text-red-600">{form.errors.student_notes}</p>}
                    </Section>

                    {isEdit && canDelete && (
                        <div className="flex justify-end pt-2">
                            <button
                                type="button"
                                onClick={() => {
                                    deleteForm.clearErrors();
                                    setDeleteStep('confirm');
                                }}
                                className="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-60"
                            >
                                {t.deleteStudent}
                            </button>
                        </div>
                    )}
                    </fieldset>
                </form>
                {isEdit && (
                    <div className="mt-6">
                        <Section title={t.accountTitle}>
                            <AccountPanel
                                t={t}
                                account={customer?.account ?? null}
                                linkableUsers={linkableStudentUsers}
                                createUrl={route('customers.account.store', customer.id)}
                                linkUrl={route('customers.account.link', customer.id)}
                                unlinkUrl={route('customers.account.unlink', customer.id)}
                                disableUrl={route('customers.account.disable', customer.id)}
                                canWrite={canWrite}
                                defaultName={[form.data.first_name, form.data.last_name].filter(Boolean).join(' ')}
                                defaultEmail={form.data.student_email || ''}
                            />
                        </Section>
                    </div>
                )}
            </div>

            {parentModal === 'father' && (
                <ParentModal title={t.fatherTitle} closeText={t.close} onClose={() => setParentModal(null)}>
                    <ParentFormFields
                        t={t}
                        form={form}
                        prefix="father"
                        customer={customer}
                        canWrite={canWrite}
                        isEdit={isEdit}
                        linkableParentUsers={linkableParentUsers}
                    />
                </ParentModal>
            )}
            {parentModal === 'mother' && (
                <ParentModal title={t.motherTitle} closeText={t.close} onClose={() => setParentModal(null)}>
                    <ParentFormFields
                        t={t}
                        form={form}
                        prefix="mother"
                        customer={customer}
                        canWrite={canWrite}
                        isEdit={isEdit}
                        linkableParentUsers={linkableParentUsers}
                    />
                </ParentModal>
            )}

            {deleteStep === 'confirm' && (
                <ParentModal title={t.deleteBlockTitle} closeText={t.cancel} onClose={() => setDeleteStep(null)}>
                    <p className="text-sm text-[#1b1c1c]">{t.deleteConfirm}</p>
                    <div className="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setDeleteStep(null)}
                            className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            {t.cancel}
                        </button>
                        <button
                            type="button"
                            onClick={() => setDeleteStep('password')}
                            className="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800"
                        >
                            {t.confirmAction}
                        </button>
                    </div>
                </ParentModal>
            )}

            {deleteStep === 'password' && (
                <ParentModal title={t.deleteBlockTitle} closeText={t.cancel} onClose={() => setDeleteStep(null)}>
                    <div className="space-y-3">
                        <p className="text-sm text-[#1b1c1c]">{t.deletePasswordStep}</p>
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-red-800">{t.currentPassword}</label>
                            <input
                                type="password"
                                value={deleteForm.data.password}
                                onChange={(e) => deleteForm.setData('password', e.target.value)}
                                className="h-10 w-full rounded-md border border-red-200 bg-white px-3 text-sm text-[#1b1c1c] focus:border-red-400 focus:outline-none"
                            />
                            {deleteForm.errors.password && <p className="mt-1 text-xs text-red-700">{deleteForm.errors.password}</p>}
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setDeleteStep(null)}
                                className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="button"
                                onClick={submitDelete}
                                className="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-60"
                                disabled={deleteForm.processing || !deleteForm.data.password}
                            >
                                {t.deleteStudent}
                            </button>
                        </div>
                    </div>
                </ParentModal>
            )}
        </AdminLayout>
    );
}

function Section({ title, children }) {
    return (
        <section className="rounded-lg border border-[#E5E5E5] bg-white p-6">
            <h2 className="font-singo text-xl uppercase tracking-[0.08em] text-[#04162e]">{title}</h2>
            <div className="mt-4">{children}</div>
        </section>
    );
}

function Grid({ children }) {
    return <div className="grid grid-cols-1 gap-3 md:grid-cols-2">{children}</div>;
}

function Field({ t, form, name, label, type = 'text', required = false }) {
    return (
        <div>
            <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-[#44474d]">
                {label}
                {required ? ' *' : ''}
            </label>
            <input
                type={type}
                value={form.data[name] ?? ''}
                required={required}
                onChange={(e) => form.setData(name, e.target.value)}
                className="h-10 w-full rounded-md border border-[#E5E5E5] bg-white px-3 text-sm text-[#1b1c1c] focus:border-[#1A2B44] focus:outline-none"
            />
            {form.errors[name] && <p className="mt-1 text-xs text-red-600">{form.errors[name]}</p>}
        </div>
    );
}

function SelectField({ form, name, label, options }) {
    return (
        <div>
            <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-[#44474d]">{label}</label>
            <select
                value={form.data[name] ?? ''}
                onChange={(e) => form.setData(name, e.target.value)}
                className="h-10 w-full rounded-md border border-[#E5E5E5] bg-white px-3 text-sm text-[#1b1c1c] focus:border-[#1A2B44] focus:outline-none"
            >
                {options.map((option) => (
                    <option key={option.value || 'empty'} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {form.errors[name] && <p className="mt-1 text-xs text-red-600">{form.errors[name]}</p>}
        </div>
    );
}

function ParentCardButton({ title, name, subtitle, buttonText, onClick }) {
    return (
        <div className="rounded-md border border-[#E5E5E5] bg-[#FCFBF7] p-4">
            <h3 className="text-sm font-semibold uppercase tracking-[0.08em] text-[#04162e]">{title}</h3>
            <p className="mt-2 text-sm font-medium text-[#1b1c1c]">{name}</p>
            <p className="mt-1 text-xs text-[#75777e]">{subtitle}</p>
            <button
                type="button"
                onClick={onClick}
                className="mt-3 rounded-md border border-[#E5E5E5] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#1A2B44] hover:bg-[#f6f3f2]"
            >
                {buttonText}
            </button>
        </div>
    );
}

function ParentModal({ title, closeText, onClose, children }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4">
            <div className="w-full max-w-2xl rounded-lg border border-[#E5E5E5] bg-white p-6 shadow-[0px_4px_20px_rgba(0,0,0,0.12)]">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="font-singo text-xl uppercase tracking-[0.08em] text-[#04162e]">{title}</h3>
                    <button type="button" onClick={onClose} className="text-sm text-[#75777e] hover:text-[#1A2B44]">
                        {closeText}
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

function ParentFormFields({ t, form, prefix, customer = null, canWrite = false, isEdit = false, linkableParentUsers = [] }) {
    const parentId = customer?.[`${prefix}_id`] ?? null;
    const account = customer?.[`${prefix}_account`] ?? null;

    return (
        <div className="grid grid-cols-1 gap-3">
            <Field t={t} form={form} name={`${prefix}_first_name`} label={t.firstName} />
            <Field t={t} form={form} name={`${prefix}_last_name`} label={t.lastName} />
            <Field t={t} form={form} name={`${prefix}_phone`} label={t.phone} />
            <Field t={t} form={form} name={`${prefix}_email`} label={t.email} type="email" />
            <div>
                <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-[#44474d]">{t.notes}</label>
                <textarea
                    rows={3}
                    value={form.data[`${prefix}_notes`] ?? ''}
                    onChange={(e) => form.setData(`${prefix}_notes`, e.target.value)}
                    className="w-full rounded-md border border-[#E5E5E5] bg-white px-3 py-2 text-sm text-[#1b1c1c] focus:border-[#1A2B44] focus:outline-none"
                />
                {form.errors[`${prefix}_notes`] && <p className="mt-1 text-xs text-red-600">{form.errors[`${prefix}_notes`]}</p>}
            </div>
            {isEdit && (
                <AccountPanel
                    t={t}
                    account={account}
                    linkableUsers={linkableParentUsers}
                    createUrl={parentId ? route('customers.parents.account.store', [customer.id, parentId]) : null}
                    linkUrl={parentId ? route('customers.parents.account.link', [customer.id, parentId]) : null}
                    unlinkUrl={parentId ? route('customers.parents.account.unlink', [customer.id, parentId]) : null}
                    disableUrl={parentId ? route('customers.parents.account.disable', [customer.id, parentId]) : null}
                    canWrite={canWrite && Boolean(parentId)}
                    defaultName={[form.data[`${prefix}_first_name`], form.data[`${prefix}_last_name`]].filter(Boolean).join(' ')}
                    defaultEmail={form.data[`${prefix}_email`] || ''}
                    blockedMessage={parentId ? null : t.saveParentFirst}
                />
            )}
        </div>
    );
}

function FileField({ t, form, field, label, existingPath }) {
    return (
        <div>
            <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-[#44474d]">{label}</label>
            <input
                type="file"
                onChange={(e) => form.setData(field, e.target.files?.[0] ?? null)}
                className="block h-10 w-full rounded-md border border-[#E5E5E5] bg-white px-3 text-sm text-[#1b1c1c] file:mr-2 file:border-0 file:bg-[#f0eded] file:px-2 file:py-1"
            />
            {existingPath ? (
                <a href={`/storage/${existingPath}`} target="_blank" rel="noreferrer" className="mt-1 block text-xs text-[#1A2B44] underline">
                    {t.currentFile}
                </a>
            ) : (
                <span className="mt-1 block text-xs text-[#75777e]">{t.noFile}</span>
            )}
            {form.errors[field] && <p className="mt-1 text-xs text-red-600">{form.errors[field]}</p>}
        </div>
    );
}

function mapCustomerToForm(customer) {
    if (!customer) {
        return {};
    }

    return {
        first_name: customer.first_name ?? '',
        last_name: customer.last_name ?? '',
        gender: customer.gender ?? '',
        tax_code: customer.tax_code ?? '',
        birth_date: customer.birth_date ?? '',
        birth_place: customer.birth_place ?? '',
        residence_address: customer.residence_address ?? '',
        residence_city_province: customer.residence_city_province ?? '',
        residence_postal_code: customer.residence_postal_code ?? '',
        student_email: customer.student_email ?? '',
        student_phone: customer.student_phone ?? '',
        parent_phone: customer.parent_phone ?? '',
        parent_email: customer.parent_email ?? '',
        course_aa_2026_27: customer.course_aa_2026_27 ?? '',
        other_courses: customer.other_courses ?? '',
        is_existing_student: !!customer.is_existing_student,
        form_filled_at: customer.form_filled_at ?? '',
        medical_certificate_expiry: customer.medical_certificate_expiry ?? '',
        father_first_name: customer.father_first_name ?? '',
        father_last_name: customer.father_last_name ?? '',
        father_phone: customer.father_phone ?? '',
        father_email: customer.father_email ?? '',
        father_notes: customer.father_notes ?? '',
        mother_first_name: customer.mother_first_name ?? '',
        mother_last_name: customer.mother_last_name ?? '',
        mother_phone: customer.mother_phone ?? '',
        mother_email: customer.mother_email ?? '',
        mother_notes: customer.mother_notes ?? '',
        student_notes: customer.student_notes ?? '',
    };
}

function getInitials(firstName, lastName) {
    const first = (firstName || '').trim().charAt(0);
    const last = (lastName || '').trim().charAt(0);
    return `${first}${last}`.toUpperCase() || 'ST';
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

    return age >= 0 ? String(age) : '—';
}
