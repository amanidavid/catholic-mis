export default function GuestLayout({ children, title, subtitle }) {
    return (
        <div className="min-h-screen bg-slate-50">
            <div className="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-10">
                <div className="w-full max-w-md">
                    <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div className="px-6 pb-6 pt-8 sm:px-10 sm:pb-8 sm:pt-10">
                            <div className="mx-auto flex w-full flex-col items-center text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-600 ring-1 ring-slate-200">
                                    LOGO
                                </div>

                                {title && (
                                    <h1 className="mt-5 text-2xl font-semibold tracking-tight text-slate-900">
                                        {title}
                                    </h1>
                                )}

                                {subtitle && (
                                    <p className="mt-2 text-sm text-slate-600">
                                        {subtitle}
                                    </p>
                                )}
                            </div>

                            <div className="mt-8">{children}</div>
                        </div>
                    </div>

                    <p className="mt-6 text-center text-xs text-slate-500">
                        © {new Date().getFullYear()} Catholic MIS
                    </p>
                </div>
            </div>
        </div>
    );
}
