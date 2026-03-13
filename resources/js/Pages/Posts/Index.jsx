import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ posts }) {
    const { delete: destroy, processing } = useForm();
    const items = posts?.data ?? [];
    const links = posts?.links ?? [];

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState(null);

    const requestDelete = (id) => {
        setPendingDeleteId(id);
        setConfirmOpen(true);
    };

    const handleConfirmDelete = () => {
        if (!pendingDeleteId) return;
        destroy(route('posts.destroy', pendingDeleteId), {
            onFinish: () => {
                setConfirmOpen(false);
                setPendingDeleteId(null);
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Posts
                        </h2>
                        <p className="text-sm text-gray-500">
                            Manage your articles with Inertia + React.
                        </p>
                    </div>
                    <Link
                        href={route('posts.create')}
                        className="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        New Post
                    </Link>
                </div>
            }
        >
            <Head title="Posts" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {items.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
                            No posts yet. Click "New Post" to get started.
                        </div>
                    ) : (
                        <div className="grid gap-4 lg:grid-cols-2">
                            {items.map((post) => (
                                <div
                                    key={post.id}
                                    className="flex flex-col justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                                >
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between text-xs uppercase tracking-wide text-gray-500">
                                            <span>
                                                {post.published
                                                    ? 'Published'
                                                    : 'Draft'}
                                            </span>
                                            <span className="text-gray-400">
                                                {post.created_at}
                                            </span>
                                        </div>
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            {post.title}
                                        </h3>
                                        <p className="text-sm text-gray-600 max-h-24 overflow-hidden">
                                            {post.body}
                                        </p>
                                    </div>
                                    <div className="mt-4 flex flex-wrap items-center gap-3 text-sm">
                                        <Link
                                            href={route('posts.edit', post.id)}
                                            className="font-semibold text-indigo-600 hover:text-indigo-800"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            disabled={processing}
                                            onClick={() =>
                                                requestDelete(post.id)
                                            }
                                            className="text-red-600 hover:text-red-800 disabled:opacity-60"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {links.length > 1 && (
                        <div className="flex flex-wrap gap-2">
                            {links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`rounded border px-3 py-1 text-sm ${
                                        link.active
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-indigo-300'
                                    } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <ConfirmDialog
                open={confirmOpen}
                type="danger"
                title="Delete post?"
                message="This will permanently remove the post. This action cannot be undone."
                confirmText="Delete post"
                cancelText="Cancel"
                onConfirm={handleConfirmDelete}
                onCancel={() => {
                    setConfirmOpen(false);
                    setPendingDeleteId(null);
                }}
            />
        </AuthenticatedLayout>
    );
}
