interface EnrollmentHeaderProps {
  onOpenCreate: () => void;
}

export default function EnrollmentHeader({ onOpenCreate }: EnrollmentHeaderProps) {
  return (
    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900">
          Manajemen KRS Akademik
        </h1>
        <p className="text-sm text-slate-500 mt-0.5">
          Optimasi Performa Skala 5 Juta Data dengan Service-Action Pattern
        </p>
      </div>
      <button
        onClick={onOpenCreate}
        className="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all font-medium shadow-sm shadow-blue-500/20 active:scale-95"
      >
        <span className="text-lg leading-none">+</span> Tambah KRS Baru
      </button>
    </div>
  );
}