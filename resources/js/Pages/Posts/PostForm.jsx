import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';

export default function PostForm({
    existingPost,
    onSuccess,
    submitText = 'Save',
}) {
    const { data, setData, post: store, put, processing, errors } = useForm({
        title: existingPost?.title ?? '',
        body: existingPost?.body ?? '',
        published: existingPost?.published ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const action = existingPost
            ? put(route('posts.update', existingPost.id), { onSuccess })
            : store(route('posts.store'), { onSuccess });

        return action;
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                <div className="md:col-span-2">
                    <InputLabel htmlFor="title" value="Title" />
                    <TextInput
                        id="title"
                        name="title"
                        value={data.title}
                        className="mt-2 block w-full"
                        onChange={(e) => setData('title', e.target.value)}
                        required
                        placeholder="Post title"
                    />
                    <InputError message={errors.title} className="mt-2" />
                </div>
                <div className="md:col-span-2">
                    <InputLabel htmlFor="body" value="Body" />
                    <textarea
                        id="body"
                        className="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows={6}
                        value={data.body}
                        onChange={(e) => setData('body', e.target.value)}
                        placeholder="Write something meaningful..."
                        required
                    />
                    <InputError message={errors.body} className="mt-2" />
                </div>
                <div className="flex items-center gap-3 md:col-span-2">
                    <input
                        id="published"
                        type="checkbox"
                        checked={data.published}
                        onChange={(e) =>
                            setData('published', e.target.checked)
                        }
                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label
                        htmlFor="published"
                        className="text-sm font-medium text-gray-700"
                    >
                        Published
                    </label>
                    <span className="text-xs text-gray-500">
                        Uncheck to save as draft.
                    </span>
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <PrimaryButton disabled={processing}>{submitText}</PrimaryButton>
                <p className="text-xs text-gray-500">
                    All fields are required unless noted otherwise.
                </p>
            </div>
        </form>
    );
}
