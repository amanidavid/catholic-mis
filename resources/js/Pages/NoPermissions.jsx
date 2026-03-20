import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function NoPermissions() {
    return (
        <AuthenticatedLayout header="No Access">
            <Head title="No Access" />

            <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h1 className="text-lg font-semibold text-slate-900">No permissions assigned</h1>
                <p className="mt-2 text-sm text-slate-600">
                    Your account does not have any permissions yet. Please contact the system administrator.
                </p>

                <div className="mt-4 flex flex-wrap gap-2">
                    <Link
                        href={route('profile.edit')}
                        className="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        View Profile
                    </Link>
                    <Link
                        href={route('dashboard')}
                        className="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                    >
                        Refresh
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
