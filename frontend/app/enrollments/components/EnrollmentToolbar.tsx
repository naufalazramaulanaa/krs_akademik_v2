"use client";

interface EnrollmentToolbarProps {
  onOpenAdvancedModal: () => void;
  onExport: () => void;
  hasActiveFilters: boolean;
}

export default function EnrollmentToolbar({
  onOpenAdvancedModal,
  onExport,
  hasActiveFilters,
}: EnrollmentToolbarProps) {
  return (
    <div className="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 className="text-base font-semibold text-slate-800">
          Daftar KRS Mahasiswa
        </h2>
        <p className="text-xs text-slate-500 mt-0.5">
          Kelola data KRS skala besar dengan filter lanjutan dan multi-sort.
        </p>
      </div>

      <div className="flex items-center gap-2.5 w-full sm:w-auto justify-end">
        {/* Tombol Buka Modal Advanced Query */}
        <button
          type="button"
          onClick={onOpenAdvancedModal}
          className={`relative flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all border shadow-sm ${
            hasActiveFilters
              ? "bg-blue-50/80 text-blue-700 border-blue-200 hover:bg-blue-100/80"
              : "bg-slate-50 text-slate-700 border-slate-200/80 hover:bg-slate-100"
          }`}
        >
          {/* Icon Filter */}
          <svg
            className="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
            />
          </svg>

          <span>Advanced Query</span>

          {/* Indikator Active Filter */}
          {hasActiveFilters && (
            <span className="flex items-center gap-1.5 ml-1 bg-blue-600 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-full">
              <span className="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
              Aktif
            </span>
          )}
        </button>

        {/* Tombol Export CSV */}
        <button
          type="button"
          onClick={onExport}
          className="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-emerald-600 text-white px-4 py-2.5 rounded-xl hover:bg-emerald-700 transition-all text-sm font-medium shadow-sm shadow-emerald-600/20 active:scale-[0.98]"
        >
          {/* Icon Export / Download */}
          <svg
            className="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
            />
          </svg>

          <span>Export CSV</span>
        </button>
      </div>
    </div>
  );
}