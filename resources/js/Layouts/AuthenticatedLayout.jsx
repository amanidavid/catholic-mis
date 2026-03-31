import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const icons = {
    dashboard: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 12l9-9 9 9M4.5 10.5V21h5v-6h5v6h5V10.5" />
        </svg>
    ),
    church: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 3l7 5v13H5V8l7-5z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 21v-6a3 3 0 016 0v6" />
        </svg>
    ),
    baptism: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 2s5 6 5 10a5 5 0 01-10 0c0-4 5-10 5-10z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 20c2.5-1.5 5.5-1.5 8 0s5.5 1.5 8 0" />
        </svg>
    ),
    marriage: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.5 14.5a4.5 4.5 0 116.4-6.4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.5 9.5a4.5 4.5 0 11-6.4 6.4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.2 10.2l3.6 3.6" />
        </svg>
    ),
    communion: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 5h8v4a4 4 0 01-8 0V5z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 21h6" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M10 15h4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v6" />
        </svg>
    ),
    confirmation: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 2c3 2.5 6 6.5 6 10.5A6 6 0 116 12.5C6 8.5 9 4.5 12 2z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6" />
        </svg>
    ),
    calendar: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 3v3M16 3v3" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 5h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 11h4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 15h4" />
        </svg>
    ),
    calendarSettings: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 3v3M16 3v3" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 5h14a2 2 0 012 2v7" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 23a2 2 0 01-2-2V7a2 2 0 012-2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19a4 4 0 108 0 4 4 0 00-8 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 16.5v1.1" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 20.4v1.1" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.6 19h1.1" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M20.3 19h1.1" />
        </svg>
    ),
    calendarCheck: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 3v3M16 3v3" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 5h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 14l2 2 4-4" />
        </svg>
    ),
    map: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 3v15" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 6v15" />
        </svg>
    ),
    group: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 11a4 4 0 100-8 4 4 0 000 8z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21v-2a4 4 0 00-3-3.87" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M16 3.13a4 4 0 010 7.75" />
        </svg>
    ),
    house: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 11l9-8 9 8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 10v11h14V10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 21v-6h6v6" />
        </svg>
    ),
    user: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 20a5 5 0 00-10 0" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 12a4 4 0 100-8 4 4 0 000 8z" />
        </svg>
    ),
    network: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 7a3 3 0 110-6 3 3 0 010 6z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 21a3 3 0 110-6 3 3 0 010 6z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 21a3 3 0 110-6 3 3 0 010 6z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 7v4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 11l-7 4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 11l7 4" />
        </svg>
    ),
    badge: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 2l3 5h5l-4 4 1 6-5-3-5 3 1-6-4-4h5l3-5z" />
        </svg>
    ),
    star: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7l3-7z" />
        </svg>
    ),
    briefcase: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M10 6V5a2 2 0 012-2h0a2 2 0 012 2v1" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 12h16" />
        </svg>
    ),
    users: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 11a4 4 0 100-8 4 4 0 000 8z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21v-2a4 4 0 00-3-3.87" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M16 3.13a4 4 0 010 7.75" />
        </svg>
    ),
    shield: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6l8-4z" />
        </svg>
    ),
    key: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 7a4 4 0 11-7.5 2H3v4h3v3h3v3h4v-5.5A4 4 0 0015 7z" />
        </svg>
    ),
    history: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 12a9 9 0 101-4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 4v4h4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 7v5l3 3" />
        </svg>
    ),
    list: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 6h13M8 12h13M8 18h13" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.5 6h.01M3.5 12h.01M3.5 18h.01" />
        </svg>
    ),
    profile: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 12a5 5 0 100-10 5 5 0 000 10zm-7 9a7 7 0 0114 0" />
        </svg>
    ),
    finance: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 2v20" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 6.5c0-1.9-2.2-3.5-5-3.5s-5 1.6-5 3.5 2.2 3.5 5 3.5 5 1.6 5 3.5-2.2 3.5-5 3.5-5-1.6-5-3.5" />
        </svg>
    ),
    coaGroups: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 6v12" />
        </svg>
    ),
    coaTypes: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 4h12v6H6V4z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 14h12v6H6v-6z" />
        </svg>
    ),
    coaSubtypes: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 6h10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 12h10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 18h10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 6h.01M5 12h.01M5 18h.01" />
        </svg>
    ),
    coaLedgers: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 12h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 17h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 7v10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 7v10" />
        </svg>
    ),
    journals: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 7h8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 11h8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 15h5" />
        </svg>
    ),
    generalLedger: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 10h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 14h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 18h16" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 6v12" />
        </svg>
    ),
    trialBalance: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 6h14" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 18h14" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 6v12" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 6v12" />
        </svg>
    ),
    doubleEntries: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 7h10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 12h10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 17h10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 7l2 2 2-2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 17l-2-2-2 2" />
        </svg>
    ),
    banks: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10l9-5 9 5" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 10v8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M10 10v8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M14 10v8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 10v8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 20h18" />
        </svg>
    ),
    bankAccounts: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <rect x="3" y="6" width="18" height="12" rx="2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 14h4" />
        </svg>
    ),
    bankTransactions: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 7h10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 7l-2-2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 7l-2 2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M17 17H7" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 17l2-2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 17l2 2" />
        </svg>
    ),
    pettyCash: (
        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
            <rect x="4" y="6" width="16" height="12" rx="2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 10h8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 14h5" />
        </svg>
    ),
};

export default function AuthenticatedLayout({ header, children }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [flashModal, setFlashModal] = useState({ open: false, type: 'success', message: '' });
    const [openGroups, setOpenGroups] = useState({});

    const permissions = useMemo(() => (auth?.user?.permissions ?? []), [auth?.user?.permissions]);
    const can = (permissionName) => Array.isArray(permissions) && permissions.includes(permissionName);

    const navGroups = useMemo(() => {
        const items = [
            {
                group: 'Dashboard',
                items: [
                    { label: 'Dashboard', routeName: 'dashboard', href: () => route('dashboard'), icon: icons.dashboard, show: true },
                ],
            },
            {
                group: 'Church Structure',
                items: [
                    { label: 'Parish', routeName: 'setup.*', href: () => route('setup.index'), icon: icons.church, show: true },
                    { label: 'Zones', routeName: 'zones.*', href: () => route('zones.index'), icon: icons.map, show: can('zones.view') },
                    { label: 'Christian Communities', routeName: 'jumuiyas.*', href: () => route('jumuiyas.index'), icon: icons.group, show: can('jumuiyas.view') },
                ],
            },
            {
                group: 'Community Records',
                items: [
                    { label: 'Members', routeName: 'members.*', href: () => route('members.index'), icon: icons.user, show: can('members.view') },
                    { label: 'Families', routeName: 'families.*', href: () => route('families.index'), icon: icons.house, show: can('families.view') },
                    { label: 'Family Relationships', routeName: 'family-relationships.*', href: () => route('family-relationships.index'), icon: icons.network, show: can('family-relationships.view') },
                    { label: 'Weekly Attendance', routeName: 'weekly-attendance.*', href: () => route('weekly-attendance.index'), icon: icons.history, show: can('weekly-attendance.view') },
                ],
            },
            {
                group: 'Attendance Reports',
                items: [
                    { label: 'Community Summaries', routeName: 'weekly-attendance.reports.community', href: () => route('weekly-attendance.reports.community'), icon: icons.group, show: can('weekly-attendance.view') },
                    { label: 'Action Lists', routeName: 'weekly-attendance.reports.action-list', href: () => route('weekly-attendance.reports.action-list'), icon: icons.list, show: can('weekly-attendance.view') },
                    { label: 'Family Attendances', routeName: 'weekly-attendance.reports.families', href: () => route('weekly-attendance.reports.families'), icon: icons.house, show: can('weekly-attendance.view') },
                    { label: 'Member Attendances', routeName: 'weekly-attendance.reports.members', href: () => route('weekly-attendance.reports.members'), icon: icons.user, show: can('weekly-attendance.view') },
                    { label: 'Attendance Audit Logs', routeName: 'weekly-attendance.reports.audit-logs', href: () => route('weekly-attendance.reports.audit-logs'), icon: icons.history, show: can('weekly-attendance.view') },
                ],
            },
            {
                group: 'Reports',
                items: [
                    { label: 'Community Summary', routeName: 'reports.community.members-by-jumuiya', href: () => route('reports.community.members-by-jumuiya'), icon: icons.group, show: can('reports.community.view') },
                ],
            },
            {
                group: 'Community Leadership',
                items: [
                    { label: 'Leadership Roles', routeName: 'jumuiya-leadership-roles.*', href: () => route('jumuiya-leadership-roles.index'), icon: icons.badge, show: can('jumuiya-leadership-roles.view') },
                    { label: 'Leadership Assignments', routeName: 'jumuiya-leaderships.*', href: () => route('jumuiya-leaderships.index'), icon: icons.star, show: can('jumuiya-leaderships.view') },
                ],
            },
            {
                group: 'Sacraments',
                items: [
                    { label: 'Baptisms', routeName: 'baptisms.*', href: () => route('baptisms.index'), icon: icons.baptism, show: can('baptisms.view') },
                    { label: 'Marriages', routeName: 'marriages.*', href: () => route('marriages.index'), icon: icons.marriage, show: can('marriages.view') },
                    { label: 'Communions', routeName: 'communions.*', href: () => route('communions.index'), icon: icons.communion, show: can('communions.view') || can('communions.parish.view') },
                    { label: 'Communion Cycles', routeName: 'communions.cycles.*', href: () => route('communions.cycles.index'), icon: icons.calendarSettings, show: can('communions.cycles.manage') || can('permissions.manage') || can('sacraments.cycle.override') },
                    { label: 'Confirmations', routeName: 'confirmations.*', href: () => route('confirmations.index'), icon: icons.confirmation, show: can('confirmations.view') || can('confirmations.parish.view') },
                    { label: 'Confirmation Cycles', routeName: 'confirmations.cycles.*', href: () => route('confirmations.cycles.index'), icon: icons.calendarCheck, show: can('confirmations.cycles.manage') || can('permissions.manage') || can('sacraments.cycle.override') },
                ],
            },
            {
                group: 'Parish Administration',
                items: [
                    { label: 'Parish Staff', routeName: 'parish-staff.*', href: () => route('parish-staff.index'), icon: icons.briefcase, show: can('parish-staff.view') },
                    { label: 'Staff Positions', routeName: 'parish-staff-positions.*', href: () => route('parish-staff-positions.index'), icon: icons.badge, show: can('parish-staff-positions.view') },
                    { label: 'Institutions', routeName: 'institutions.*', href: () => route('institutions.index'), icon: icons.church, show: can('institutions.view') },
                ],
            },
            {
                group: 'Finance',
                items: [
                    {
                        label: 'Chart of Accounts',
                        isSection: true,
                        show: true,
                        children: [
                            { label: 'Account Groups', routeName: 'chart-of-accounts.groups.*', href: () => route('chart-of-accounts.groups.index'), icon: icons.coaGroups, show: can('chart-of-accounts.groups.view') },
                            { label: 'Account Types', routeName: 'chart-of-accounts.types.*', href: () => route('chart-of-accounts.types.index'), icon: icons.coaTypes, show: can('chart-of-accounts.types.view') },
                            { label: 'Account Subtypes', routeName: 'chart-of-accounts.subtypes.*', href: () => route('chart-of-accounts.subtypes.index'), icon: icons.coaSubtypes, show: can('chart-of-accounts.subtypes.view') },
                            { label: 'Ledgers', routeName: 'chart-of-accounts.ledgers.*', href: () => route('chart-of-accounts.ledgers.index'), icon: icons.coaLedgers, show: can('chart-of-accounts.ledgers.view') },
                        ],
                    },

                    {
                        label: 'Banking',
                        isSection: true,
                        show: true,
                        children: [
                            { label: 'Banks', routeName: 'finance.banks.*', href: () => route('finance.banks.index'), icon: icons.banks, show: can('finance.banks.view') },
                            { label: 'Bank Accounts', routeName: 'finance.bank-accounts.*', href: () => route('finance.bank-accounts.index'), icon: icons.bankAccounts, show: can('finance.bank-accounts.view') },
                            { label: 'Bank Transactions', routeName: 'finance.bank-account-transactions.*', href: () => route('finance.bank-account-transactions.index'), icon: icons.bankTransactions, show: can('finance.bank-account-transactions.view') },
                        ],
                    },

                    {
                        label: 'Accounting',
                        isSection: true,
                        show: true,
                        children: [
                            { label: 'Double Entries', routeName: 'finance.double-entries.*', href: () => route('finance.double-entries.index'), icon: icons.doubleEntries, show: can('finance.double-entries.view') },
                            { label: 'Journals', routeName: 'finance.journals.*', href: () => route('finance.journals.index'), icon: icons.journals, show: can('finance.journals.view') },
                            { label: 'General Ledger', routeName: 'finance.general-ledger.*', href: () => route('finance.general-ledger.index'), icon: icons.generalLedger, show: can('finance.general-ledger.view') },
                            { label: 'Trial Balance', routeName: 'finance.trial-balance.*', href: () => route('finance.trial-balance.index'), icon: icons.trialBalance, show: can('finance.trial-balance.view') },
                        ],
                    },

                    {
                        label: 'Petty Cash',
                        isSection: true,
                        show: true,
                        children: [
                            { label: 'Petty Cash Funds', routeName: 'finance.petty-cash-funds.*', href: () => route('finance.petty-cash-funds.index'), icon: icons.pettyCash, show: can('finance.petty-cash-funds.view') },
                            { label: 'Petty Cash Vouchers', routeName: 'finance.petty-cash-vouchers.*', href: () => route('finance.petty-cash-vouchers.index'), icon: icons.pettyCash, show: can('finance.petty-cash-vouchers.view') },
                            { label: 'Petty Cash Replenishments', routeName: 'finance.petty-cash-replenishments.*', href: () => route('finance.petty-cash-replenishments.index'), icon: icons.pettyCash, show: can('finance.petty-cash-replenishments.view') },
                            { label: 'Petty Cash Book', routeName: 'finance.petty-cash-book.*', href: () => route('finance.petty-cash-book.index'), icon: icons.pettyCash, show: can('finance.petty-cash-book.view') },
                        ],
                    },
                ],
            },
            {
                group: 'System Administration',
                items: [
                    { label: 'Users', routeName: 'access-control.users.*', href: () => route('access-control.users.index'), icon: icons.users, show: can('permissions.manage') },
                    { label: 'Roles', routeName: 'access-control.roles.*', href: () => route('access-control.roles.index'), icon: icons.shield, show: can('permissions.manage') },
                    { label: 'Permissions', routeName: 'access-control.permissions.*', href: () => route('access-control.permissions.index'), icon: icons.key, show: can('permissions.manage') },
                    { label: 'Audit Logs', routeName: 'audit-logs.*', href: () => route('audit-logs.index'), icon: icons.history, show: can('audit-logs.view') },
                ],
            },
            {
                group: 'Account',
                items: [
                    { label: 'Profile', routeName: 'profile.*', href: () => route('profile.edit'), icon: icons.profile, show: true },
                ],
            },
        ];

        return items
            .map((g) => {
                const nextItems = (g.items ?? [])
                    .map((i) => (
                        i.children
                            ? { ...i, children: (i.children ?? []).filter((child) => child.show) }
                            : i
                    ))
                    .filter((i) => i.show && (!i.children || i.children.length > 0));

                return { ...g, items: nextItems };
            })
            .filter((g) => g.items.length > 0);
    }, [permissions]);

    useEffect(() => {
        const next = {};
        (navGroups ?? []).forEach((g) => {
            const isActive = (g.items ?? []).some((i) =>
                (i.routeName && route().current(i.routeName))
                || (i.children ?? []).some((child) => child.routeName && route().current(child.routeName))
            );
            next[g.group] = isActive;

            (g.items ?? []).forEach((i) => {
                if (!i.isSection) return;
                const sectionKey = `${g.group}:${i.label}`;
                const sectionActive = (i.children ?? []).some((child) => child.routeName && route().current(child.routeName));
                next[sectionKey] = sectionActive;
            });
        });
        setOpenGroups(next);
    }, [navGroups]);

    const toggleGroup = (groupName) => {
        setOpenGroups((prev) => ({
            ...(prev ?? {}),
            [groupName]: !prev?.[groupName],
        }));
    };

    const renderNavLink = (item, keyPrefix = '') => {
        const active = route().current(item.routeName);
        return (
            <Link
                key={`${keyPrefix}${item.label}`}
                href={item.href()}
                className={`flex items-center gap-3 rounded-lg py-2 text-sm font-medium transition ${active
                    ? 'border-l-4 border-white/90 bg-indigo-600/25 pl-2 pr-3 text-white ring-1 ring-indigo-300/10'
                    : 'border-l-4 border-transparent px-3 text-slate-200/90 hover:bg-white/10 hover:text-white'
                    }`}
            >
                <span
                    className={`flex h-8 w-8 items-center justify-center rounded-md ${active
                        ? 'bg-indigo-500/25 text-indigo-100 ring-1 ring-indigo-200/20'
                        : 'bg-white/10 text-slate-100/90'
                        }`}
                >
                    {item.icon}
                </span>
                <span>{item.label}</span>
            </Link>
        );
    };

    useEffect(() => {
        const isSetup = route().current('setup.*');

        if (flash?.success && !isSetup) {
            setFlashModal({ open: true, type: 'success', message: flash.success });
        }

        if (flash?.error) {
            setFlashModal({ open: true, type: 'error', message: flash.error });
        }
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        const handler = (event) => {
            const type = event?.detail?.type;
            const message = event?.detail?.message;
            if (!message) return;
            setFlashModal({ open: true, type: type === 'error' ? 'error' : 'success', message });
        };

        window.addEventListener('app:flash', handler);
        return () => window.removeEventListener('app:flash', handler);
    }, []);

    return (
        <div className="flex min-h-screen bg-slate-50 text-slate-800">
            {/* Sidebar */}
            <div
                className={`fixed inset-y-0 left-0 z-30 w-72 transform border-r border-indigo-900/50 bg-gradient-to-b from-indigo-950 via-indigo-900 to-indigo-950 transition duration-200 ease-in-out lg:static lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}
            >
                <div className="flex h-16 items-center justify-between border-b border-indigo-900/50 px-4">
                    <Link href="/" className="flex items-center gap-2">
                        <ApplicationLogo className="h-8 w-8 fill-indigo-600" />
                        <div className="text-lg font-semibold text-white">
                            Parish MIS
                        </div>
                    </Link>
                    <button
                        onClick={() => setSidebarOpen(false)}
                        className="rounded-md p-2 text-slate-200 hover:bg-white/10 lg:hidden"
                        title="Close sidebar"
                    >
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="px-3 py-4">
                    {navGroups.map((group) => (
                        <div key={group.group} className="mb-6">
                            {(() => {
                                const groupActive = (group.items ?? []).some((i) =>
                                    (i.routeName && route().current(i.routeName))
                                    || (i.children ?? []).some((child) => child.routeName && route().current(child.routeName))
                                );
                                return (
                                    <button
                                        type="button"
                                        onClick={() => toggleGroup(group.group)}
                                        className={`flex w-full items-center justify-between rounded-lg border-l-4 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider transition ${groupActive
                                            ? 'border-white/90 bg-indigo-600/20 text-white hover:bg-indigo-600/25'
                                            : 'border-transparent text-slate-200/80 hover:bg-white/10'
                                            }`}
                                    >
                                        <span>{group.group}</span>
                                        <svg
                                            className={`h-4 w-4 transition ${openGroups?.[group.group] ? 'rotate-180' : ''}`}
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                );
                            })()}

                            {openGroups?.[group.group] && (
                                <nav className="mt-2 space-y-1">
                                    {group.items.map((item) => {
                                        if (item.isSection) {
                                            const sectionKey = `${group.group}:${item.label}`;
                                            const sectionActive = (item.children ?? []).some((child) => child.routeName && route().current(child.routeName));
                                            return (
                                                <div key={`section-${group.group}-${item.label}`} className="pt-2 first:pt-0">
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleGroup(sectionKey)}
                                                        className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider transition ${sectionActive
                                                            ? 'text-slate-100'
                                                            : 'text-slate-300/70 hover:bg-white/5 hover:text-slate-200'
                                                            }`}
                                                    >
                                                        <span>{item.label}</span>
                                                        <svg
                                                            className={`h-3.5 w-3.5 transition ${openGroups?.[sectionKey] ? 'rotate-180' : ''}`}
                                                            fill="none"
                                                            stroke="currentColor"
                                                            strokeWidth="2"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>

                                                    {openGroups?.[sectionKey] && (
                                                        <div className="mt-1 space-y-1">
                                                            {(item.children ?? []).map((child) => renderNavLink(child, `${sectionKey}:`))}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        }

                                        return renderNavLink(item);
                                    })}
                                </nav>
                            )}
                        </div>
                    ))}
                </div>

            </div>

            {/* Overlay for mobile */}
            {sidebarOpen && (
                <div
                    onClick={() => setSidebarOpen(false)}
                    className="fixed inset-0 z-20 bg-slate-900/30 backdrop-blur-sm lg:hidden"
                />
            )}

            {/* Main area */}
            <div className="flex flex-1 flex-col">
                <header className="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div className="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => setSidebarOpen(true)}
                                className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                            >
                                <svg
                                    className="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                </svg>
                            </button>
                            {header && (
                                <div className="text-lg font-semibold text-slate-900">
                                    {header}
                                </div>
                            )}
                        </div>

                        <div className="flex items-center gap-3">
                            <div className="hidden items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-500 sm:flex">
                                <svg
                                    className="h-4 w-4 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                                    />
                                </svg>
                                <span>Search...</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <button className="rounded-full border border-slate-200 bg-white p-2 text-slate-500 transition hover:bg-slate-50">
                                    <svg
                                        className="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M15 17h5l-1.4-4.2A2 2 0 0016.7 11H7.3a2 2 0 00-1.9 1.8L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                        />
                                    </svg>
                                </button>
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button className="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                                {user.name?.[0] || 'U'}
                                            </span>
                                            <span className="hidden sm:block">
                                                {user.name}
                                            </span>
                                            <svg
                                                className="h-4 w-4 text-slate-400"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="1.8"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </button>
                                    </Dropdown.Trigger>
                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <Modal show={flashModal.open} onClose={() => setFlashModal((p) => ({ ...p, open: false }))} maxWidth="md">
                            <div className="p-6">
                                <div className="flex items-start gap-3">
                                    <div className={`mt-0.5 flex h-9 w-9 items-center justify-center rounded-full ${flashModal.type === 'error'
                                        ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-100'
                                        : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                        }`}>
                                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                            {flashModal.type === 'error' ? (
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-7.4 12.8A2 2 0 004.62 20h14.76a2 2 0 001.73-3.34l-7.4-12.8a2 2 0 00-3.42 0z" />
                                            ) : (
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                            )}
                                        </svg>
                                    </div>
                                    <div className="flex-1">
                                        <div className="text-base font-semibold text-slate-900">
                                            {flashModal.type === 'error' ? 'Action failed' : 'Success'}
                                        </div>
                                        <div className="mt-1 text-sm text-slate-600">{flashModal.message}</div>
                                    </div>
                                </div>
                                <div className="mt-6 flex justify-end">
                                    <button
                                        type="button"
                                        onClick={() => setFlashModal((p) => ({ ...p, open: false }))}
                                        className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                    >
                                        OK
                                    </button>
                                </div>
                            </div>
                        </Modal>

                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
