import { Enrollment, Meta } from "../type";

interface EnrollmentTableProps {
  data: Enrollment[];
  meta: Meta;
  loading: boolean;
  isMounted: boolean;
  page: number;
  pageSize: number;
  setPage: React.Dispatch<React.SetStateAction<number>>;
  setPageSize: (val: number) => void;
  sortField: string;
  sortDir: "asc" | "desc";
  onSort: (field: string) => void;
  onEdit: (item: Enrollment) => void;
  onDelete: (id: number) => void;
}

export default function EnrollmentTable({
  data,
  meta,
  loading,
  isMounted,
  page,
  pageSize,
  setPage,
  setPageSize,
  sortField,
  sortDir,
  onSort,
  onEdit,
  onDelete,
}: EnrollmentTableProps) {
  // Helper Indikator Sorting per Header
  const renderSortIcon = (field: string) => {
    if (sortField !== field) {
      return <span className="text-slate-300 ml-1.5 font-normal">↕</span>;
    }
    return sortDir === "asc" ? (
      <span className="text-blue-600 ml-1.5 font-bold">↑</span>
    ) : (
      <span className="text-blue-600 ml-1.5 font-bold">↓</span>
    );
  };

  return (
    <div className="space-y-3">
      {/* Meta Info Bar */}
      <div className="flex justify-between items-center px-2 text-xs font-medium text-slate-500">
        <span>
          Total Data:{" "}
          <strong className="text-slate-700">
            {(meta.total || 0).toLocaleString()}
          </strong>{" "}
          baris
        </span>
        <span className="bg-blue-50 text-blue-700 px-3 py-1 rounded-full border border-blue-100">
          Waktu Query:{" "}
          <strong className="font-semibold">{meta.query_time_ms ?? 0} ms</strong>
        </span>
      </div>

      {/* Tabel Data */}
      <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left text-sm">
            <thead className="bg-slate-50 text-slate-600 border-b border-slate-100 select-none">
              <tr>
                {[
                  { key: "student_nim", label: "NIM" },
                  { key: "student_name", label: "Nama Mahasiswa" },
                  { key: "course_code", label: "Kode MK" },
                  { key: "course_name", label: "Mata Kuliah" },
                  { key: "semester", label: "Semester" },
                  { key: "academic_year", label: "Tahun Ajaran" },
                  { key: "status", label: "Status" },
                ].map((col) => (
                  <th
                    key={col.key}
                    className="py-3.5 px-4 font-semibold cursor-pointer hover:text-blue-600 transition-colors"
                    onClick={() => onSort(col.key)}
                  >
                    <div className="flex items-center">
                      <span>{col.label}</span>
                      {renderSortIcon(col.key)}
                    </div>
                  </th>
                ))}
                <th className="py-3.5 px-4 font-semibold text-center">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan={8} className="text-center py-12 text-slate-400">
                    <div className="flex justify-center items-center gap-2">
                      <div className="w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                      Memuat data...
                    </div>
                  </td>
                </tr>
              ) : data.length === 0 ? (
                <tr>
                  <td colSpan={8} className="text-center py-12 text-slate-400">
                    Tidak ada data ditemukan.
                  </td>
                </tr>
              ) : (
                data.map((item) => (
                  <tr
                    key={item.id}
                    className="hover:bg-blue-50/40 transition-colors group"
                  >
                    <td className="py-3.5 px-4 font-medium text-slate-900">
                      {item.student_nim}
                    </td>
                    <td className="py-3.5 px-4 text-slate-700">
                      {item.student_name}
                    </td>
                    <td className="py-3.5 px-4 font-mono text-xs text-slate-600">
                      {item.course_code}
                    </td>
                    <td className="py-3.5 px-4 text-slate-700">
                      {item.course_name}
                    </td>
                    <td className="py-3.5 px-4 text-slate-600">
                      {item.semester}
                    </td>
                    <td className="py-3.5 px-4 text-slate-600">
                      {item.academic_year}
                    </td>
                    <td className="py-3.5 px-4">
                      <span
                        className={`px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide ${
                          item.status === "APPROVED"
                            ? "bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                            : item.status === "REJECTED"
                              ? "bg-rose-50 text-rose-700 border border-rose-200/60"
                              : item.status === "SUBMITTED"
                                ? "bg-amber-50 text-amber-700 border border-amber-200/60"
                                : "bg-slate-100 text-slate-600 border border-slate-200/60"
                        }`}
                      >
                        {item.status}
                      </span>
                    </td>
                    <td className="py-3.5 px-4 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button
                          onClick={() => onEdit(item)}
                          className="px-2.5 py-1 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors cursor-pointer"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => onDelete(item.id)}
                          className="px-2.5 py-1 text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors cursor-pointer"
                        >
                          Hapus
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Controls */}
        <div className="flex flex-col sm:flex-row justify-between items-center p-4 bg-slate-50/50 border-t border-slate-100 gap-4">
          <div className="flex items-center gap-2 text-sm text-slate-600">
            <span>Baris per halaman:</span>
            <select
              value={pageSize}
              onChange={(e) => {
                setPageSize(Number(e.target.value));
                setPage(1);
              }}
              className="border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>

          <div className="flex items-center gap-3">
            {(() => {
              const totalPages =
                meta.last_page || Math.ceil((meta.total || 0) / pageSize) || 1;

              return (
                <>
                  <button
                    disabled={page === 1 || loading}
                    onClick={() => setPage((p) => Math.max(p - 1, 1))}
                    className="border border-slate-200 px-4 py-1.5 rounded-xl disabled:opacity-40 hover:bg-white bg-slate-50 text-sm font-medium text-slate-700 transition-all shadow-sm cursor-pointer disabled:cursor-not-allowed"
                  >
                    Sebelumnya
                  </button>

                  <span className="text-sm font-semibold text-slate-700">
                    {page} of {totalPages.toLocaleString()}
                  </span>

                  <button
                    disabled={page >= totalPages || loading}
                    onClick={() => setPage((p) => p + 1)}
                    className="border border-slate-200 px-4 py-1.5 rounded-xl disabled:opacity-40 hover:bg-white bg-slate-50 text-sm font-medium text-slate-700 transition-all shadow-sm cursor-pointer disabled:cursor-not-allowed"
                  >
                    Selanjutnya
                  </button>
                </>
              );
            })()}
          </div>
        </div>
      </div>
    </div>
  );
}