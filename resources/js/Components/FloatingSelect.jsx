import InputError from '@/Components/InputError';

export default function FloatingSelect({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    required = false,
    className = '',
    selectClassName = '',
    children,
    ...props
}) {
    const labelNode = (() => {
        const source = label ?? '';
        const withStar = required && !source.includes('*') ? `${source} *` : source;
        const starIndex = withStar.lastIndexOf('*');

        if (starIndex === -1) {
            return withStar;
        }

        const before = withStar.slice(0, starIndex).replace(/\s+$/, '');
        const after = withStar.slice(starIndex + 1);

        return (
            <>
                {before}
                <span className="ml-1 font-semibold text-red-600">*</span>
                {after}
            </>
        );
    })();

    return (
        <div className={`relative ${className}`}>
            <select
                id={id}
                value={value}
                onChange={onChange}
                className={`peer h-11 w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 pt-5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 ${selectClassName}`}
                {...props}
            >
                {children}
            </select>

            <div className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <label
                htmlFor={id}
                className="pointer-events-none absolute left-3 top-2.5 origin-[0] -translate-y-1 scale-75 text-xs font-semibold text-slate-500 transition-all peer-focus:top-2.5 peer-focus:-translate-y-1 peer-focus:scale-75 peer-focus:text-xs peer-focus:font-semibold peer-focus:text-indigo-600"
            >
                {labelNode}
            </label>

            {hint && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
            <InputError className="mt-2" message={error} />
        </div>
    );
}
