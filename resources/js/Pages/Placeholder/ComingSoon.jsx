import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

const TEXT = {
    it: {
        soon: 'Presto disponibile',
        coursesAndGroups: 'Corsi e gruppi',
        scheduleService: 'Servizio orari',
        documents: 'Documenti',
        communication: 'Comunicazioni',
        events: 'Eventi',
        archive: 'Archivio',
        costumeService: 'Servizio costumi',
    },
    en: {
        soon: 'Coming soon',
        coursesAndGroups: 'Courses and groups',
        scheduleService: 'Schedule service',
        documents: 'Documents',
        communication: 'Communication',
        events: 'Events',
        archive: 'Archive',
        costumeService: 'Costume service',
    },
    ru: {
        soon: 'Скоро будет',
        coursesAndGroups: 'Курсы и группы',
        scheduleService: 'Сервис расписаний',
        documents: 'Документы',
        communication: 'Коммуникация',
        events: 'События',
        archive: 'Архив',
        costumeService: 'Сервис костюмов',
    },
    uk: {
        soon: 'Скоро буде',
        coursesAndGroups: 'Курси і групи',
        scheduleService: 'Сервіс розкладів',
        documents: 'Документи',
        communication: 'Комунікація',
        events: 'Події',
        archive: 'Архів',
        costumeService: 'Сервіс костюмів',
    },
};

export default function ComingSoon({ section = 'documents' }) {
    const { locale = 'it' } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;
    const sectionTitle = t[section] ?? section;

    return (
        <AdminLayout title={sectionTitle}>
            <Head title={sectionTitle} />

            <div className="rounded-lg border border-slate-200 bg-white px-6 py-16 text-center">
                <h2 className="font-singo text-3xl text-[#04162e]">{sectionTitle}</h2>
                <p className="mt-4 text-lg font-semibold text-[#1A2B44]">{t.soon}</p>
            </div>
        </AdminLayout>
    );
}
