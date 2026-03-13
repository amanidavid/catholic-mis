export default function ModalHeader({ title, subtitle, onClose, showRequiredNote = false, children }) {
    return (
        <div className="-m-6 mb-4 rounded-t-lg bg-gradient-to-r from-indigo-700 via-indigo-600 to-indigo-700 px-6 py-5 text-white">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-lg font-semibold">{title}</h2>
                    {subtitle && <p className="mt-1 text-sm text-indigo-100">{subtitle}</p>}
                    {showRequiredNote && (
                        <p className="mt-2 text-xs text-indigo-100/90">
                            Fields marked <span className="font-semibold text-white">*</span> are required.
                        </p>
                    )}
                    {children}
                </div>
                <button
                    type="button"
                    onClick={onClose}
                    className="inline-flex h-10 items-center rounded-lg bg-white/10 px-4 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20"
                >
                    Close
                </button>
            </div>
        </div>
    );
}
