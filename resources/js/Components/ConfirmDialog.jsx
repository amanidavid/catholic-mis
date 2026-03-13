import { Fragment } from 'react';

export default function ConfirmDialog({
    open,
    title = 'Are you sure?',
    message = 'This action cannot be undone.',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    type = 'danger',
    onConfirm,
    onCancel,
    onClose,
}) {
    if (!open) return null;

    const handleCancel = onCancel ?? onClose;

    const confirmStyles =
        type === 'danger'
            ? 'bg-red-600 hover:bg-red-700 text-white'
            : 'bg-indigo-600 hover:bg-indigo-700 text-white';

    return (
        <Fragment>
            <div className="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm" />
            <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                    <div className="flex items-start gap-3">
                        <div
                            className={`mt-1 flex h-9 w-9 items-center justify-center rounded-full ${type === 'danger'
                                    ? 'bg-red-100 text-red-600'
                                    : 'bg-indigo-100 text-indigo-600'
                                }`}
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M12 9v4m0 4h.01M4.93 4.93l14.14 14.14M12 5a7 7 0 00-7 7 7 7 0 0011.9 4.9"
                                />
                            </svg>
                        </div>
                        <div className="flex-1">
                            <h2 className="text-base font-semibold text-slate-900">
                                {title}
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                {message}
                            </p>
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={handleCancel}
                            className="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            {cancelText}
                        </button>
                        <button
                            type="button"
                            onClick={onConfirm}
                            className={`rounded-md px-4 py-2 text-sm font-semibold shadow-sm ${confirmStyles}`}
                        >
                            {confirmText}
                        </button>
                    </div>
                </div>
            </div>
        </Fragment>
    );
}

