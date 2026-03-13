import InputError from '@/Components/InputError';
import { Combobox } from '@headlessui/react';
import axios from 'axios';
import { useEffect, useMemo, useRef, useState } from 'react';

export default function SearchableFamilyRelationshipSelect({
    id,
    label,
    value,
    onChange,
    disabled = false,
    error,
}) {
    const [query, setQuery] = useState('');
    const [options, setOptions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const lastFetchRef = useRef(0);

    const selected = useMemo(() => {
        if (!value) return null;
        return options.find((o) => o.uuid === value) ?? { uuid: value, name: value };
    }, [value, options]);

    const fetchOptions = async (nextQuery) => {
        const fetchId = Date.now();
        lastFetchRef.current = fetchId;
        setLoading(true);

        try {
            const res = await axios.get(route('family-relationships.lookup'), {
                params: {
                    q: typeof nextQuery === 'string' ? nextQuery.trim() : '',
                },
            });

            if (lastFetchRef.current !== fetchId) return;

            const data = res?.data?.data;
            setOptions(Array.isArray(data) ? data : []);
        } catch {
            if (lastFetchRef.current !== fetchId) return;
            setOptions([]);
        } finally {
            if (lastFetchRef.current !== fetchId) return;
            setLoading(false);
        }
    };

    useEffect(() => {
        if (disabled) return;

        const t = setTimeout(() => {
            fetchOptions(query);
        }, 250);

        return () => clearTimeout(t);
    }, [query, disabled]);

    useEffect(() => {
        if (disabled) {
            setOpen(false);
        }
    }, [disabled]);

    return (
        <div className="relative">
            <Combobox value={selected} onChange={(opt) => onChange(opt?.uuid ?? '')} disabled={disabled}>
                {() => (
                    <div className="relative">
                        <Combobox.Input
                            id={id}
                            className={`peer h-11 w-full rounded-lg border border-slate-300 bg-white px-3 pt-5 pr-16 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 ${error ? 'border-rose-300 focus:border-rose-400' : 'focus:border-indigo-300'}`}
                            displayValue={(opt) => opt?.name ?? ''}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search relationship..."
                            onFocus={() => {
                                setOpen(true);
                                if (options.length === 0 && !loading) {
                                    fetchOptions(query);
                                }
                            }}
                            onClick={() => {
                                setOpen(true);
                                if (options.length === 0 && !loading) {
                                    fetchOptions(query);
                                }
                            }}
                            onBlur={() => {
                                window.setTimeout(() => setOpen(false), 150);
                            }}
                        />

                        <Combobox.Label
                            htmlFor={id}
                            className="pointer-events-none absolute left-3 top-2.5 origin-[0] -translate-y-1 scale-75 text-xs font-semibold text-slate-500 transition-all peer-focus:top-2.5 peer-focus:-translate-y-1 peer-focus:scale-75 peer-focus:text-xs peer-focus:font-semibold peer-focus:text-indigo-600"
                        >
                            {label}
                        </Combobox.Label>

                        {!!value && !disabled && (
                            <button
                                type="button"
                                onClick={() => {
                                    setQuery('');
                                    setOptions([]);
                                    onChange('');
                                }}
                                className="absolute inset-y-0 right-10 flex items-center px-2 text-slate-400 hover:text-slate-600"
                                title="Clear"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        )}

                        <Combobox.Button
                            type="button"
                            className="absolute inset-y-0 right-2 flex items-center px-2 text-slate-400 hover:text-slate-600"
                            aria-label="Toggle relationships"
                            onClick={() => setOpen((v) => !v)}
                        >
                            {loading ? (
                                <span className="text-xs text-slate-500">Loading...</span>
                            ) : (
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            )}
                        </Combobox.Button>

                        {open && (
                            <Combobox.Options
                                static
                                className="absolute z-50 mt-2 max-h-60 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg"
                            >
                                {options.length === 0 ? (
                                    <div className="px-3 py-2 text-slate-500">
                                        {query.trim() !== '' ? 'No matches.' : 'Click the arrow to view relationships, or type to search.'}
                                    </div>
                                ) : (
                                    options.map((opt) => (
                                        <Combobox.Option
                                            key={opt.uuid}
                                            value={opt}
                                            className={({ active }) =>
                                                `cursor-pointer px-3 py-2 ${active ? 'bg-indigo-50 text-indigo-900' : 'text-slate-700'}`
                                            }
                                        >
                                            <div className="font-semibold">{opt.name}</div>
                                        </Combobox.Option>
                                    ))
                                )}
                            </Combobox.Options>
                        )}
                    </div>
                )}
            </Combobox>

            <InputError className="mt-2" message={error} />
        </div>
    );
}
