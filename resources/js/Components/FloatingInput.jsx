import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';

export default function FloatingInput({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    required = false,
    type = 'text',
    className = '',
    inputClassName = '',
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
            <TextInput
                id={id}
                type={type}
                value={value}
                onChange={onChange}
                placeholder=" "
                className={`peer h-11 w-full rounded-lg border-slate-300 bg-white px-3 pt-5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 ${inputClassName}`}
                {...props}
            />
            <label
                htmlFor={id}
                className="pointer-events-none absolute left-3 top-2.5 origin-[0] -translate-y-1 scale-75 text-xs font-semibold text-slate-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-placeholder-shown:text-sm peer-placeholder-shown:font-medium peer-focus:top-2.5 peer-focus:-translate-y-1 peer-focus:scale-75 peer-focus:text-xs peer-focus:font-semibold peer-focus:text-indigo-600"
            >
                {labelNode}
            </label>
            {hint && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
            <InputError className="mt-2" message={error} />
        </div>
    );
}
