import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PostForm from './PostForm';
import { Head, Link, router } from '@inertiajs/react';

export default function Edit({ post }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Edit Post
                        </h2>
                        <p className="text-sm text-gray-500">
                            Update the content and publish settings.
                        </p>
                    </div>
                    <Link
                        href={route('posts.index')}
                        className="text-sm font-semibold text-gray-600 hover:text-gray-900"
                    >
                        Back to Posts
                    </Link>
                </div>
            }
        >
            <Head title={`Edit - ${post.title}`} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <PostForm
                            existingPost={post}
                            submitText="Update"
                            onSuccess={() =>
                                router.visit(route('posts.index'))
                            }
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
