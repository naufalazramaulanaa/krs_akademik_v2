import React, { useState } from "react";

interface EnrollmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  editingId: number | null;
  formData: any;
  setFormData: React.Dispatch<React.SetStateAction<any>>;
  errorMessage: string;
  onSave: (e: React.FormEvent) => void;
}

export default function EnrollmentModal({
  isOpen,
  onClose,
  editingId,
  formData,
  setFormData,
  errorMessage,
  onSave,
}: EnrollmentModalProps) {
  // State untuk menampung error per-field
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  if (!isOpen) return null;

  // Fungsi Validasi Ketat Frontend (TS-03)
  const validateForm = (): boolean => {
    const errors: Record<string, string> = {};

    if (!editingId) {
      // 1. Validasi Student NIM (8-12 digit angka, tanpa spasi)
      if (!formData.student_nim) {
        errors.student_nim = "NIM wajib diisi.";
      } else if (!/^\d{8,12}$/.test(formData.student_nim)) {
        errors.student_nim = "NIM harus berupa 8-12 digit angka.";
      }

      // 2. Validasi Student Name (3-100 karakter)
      if (!formData.student_name) {
        errors.student_name = "Nama mahasiswa wajib diisi.";
      } else if (formData.student_name.length < 3 || formData.student_name.length > 100) {
        errors.student_name = "Nama harus antara 3 hingga 100 karakter.";
      }

      // 3. Validasi Student Email
      if (!formData.student_email) {
        errors.student_email = "Email wajib diisi.";
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.student_email)) {
        errors.student_email = "Format email tidak valid.";
      }

      // 4. Validasi Course Code (Format misal: IF101)
      if (!formData.course_code) {
        errors.course_code = "Kode mata kuliah wajib diisi.";
      } else if (!/^[A-Z]{2,4}[0-9]{3}$/.test(formData.course_code)) {
        errors.course_code = "Format kode MK tidak sesuai (contoh: IF101).";
      }

      // 5. Validasi Course Name (3-120 karakter)
      if (!formData.course_name) {
        errors.course_name = "Nama mata kuliah wajib diisi.";
      } else if (formData.course_name.length < 3 || formData.course_name.length > 120) {
        errors.course_name = "Nama MK harus antara 3 hingga 120 karakter.";
      }

      // 6. Validasi Course Credits (1-6 SKS)
      if (!formData.course_credits) {
        errors.course_credits = "SKS wajib diisi.";
      } else if (formData.course_credits < 1 || formData.course_credits > 6) {
        errors.course_credits = "SKS harus antara 1-6.";
      }
    }

    // 7. Validasi Academic Year (Format YYYY/YYYY)
    if (!formData.academic_year) {
      errors.academic_year = "Tahun ajaran wajib diisi.";
    } else if (!/^\d{4}\/\d{4}$/.test(formData.academic_year)) {
      errors.academic_year = "Format tahun ajaran harus YYYY/YYYY (cth: 2025/2026).";
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (validateForm()) {
      onSave(e);
    }
  };

  return (
    <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fadeIn">
      <div className="bg-white rounded-2xl shadow-xl border border-slate-100 p-6 sm:p-8 max-w-lg w-full space-y-6">
        <div className="flex justify-between items-center border-b border-slate-100 pb-4">
          <h2 className="text-xl font-bold text-slate-900">
            {editingId ? "Edit Data KRS" : "Tambah KRS Baru"}
          </h2>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 text-xl font-bold">
            ×
          </button>
        </div>

        {errorMessage && (
          <div className="bg-rose-50 text-rose-700 p-3.5 rounded-xl border border-rose-200 text-sm font-medium">
            {errorMessage}
          </div>
        )}

        <form onSubmit={handleSubmit} noValidate className="space-y-4">
          {!editingId && (
            <>
              <div className="text-xs font-semibold tracking-wider text-blue-600 uppercase pt-2">
                Data Mahasiswa
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <input
                    type="text"
                    placeholder="NIM (8-12 digit)"
                    value={formData.student_nim || ""}
                    onChange={(e) => {
                      setFormData({ ...formData, student_nim: e.target.value });
                      setFieldErrors({ ...fieldErrors, student_nim: "" });
                    }}
                    className={`border p-2.5 rounded-xl text-sm w-full focus:outline-none ${
                      fieldErrors.student_nim ? "border-rose-500 bg-rose-50/30" : "border-slate-200"
                    }`}
                  />
                  {fieldErrors.student_nim && (
                    <p className="text-rose-600 text-xs mt-1 font-medium">{fieldErrors.student_nim}</p>
                  )}
                </div>

                <div>
                  <input
                    type="email"
                    placeholder="Email Mahasiswa"
                    value={formData.student_email || ""}
                    onChange={(e) => {
                      setFormData({ ...formData, student_email: e.target.value });
                      setFieldErrors({ ...fieldErrors, student_email: "" });
                    }}
                    className={`border p-2.5 rounded-xl text-sm w-full focus:outline-none ${
                      fieldErrors.student_email ? "border-rose-500 bg-rose-50/30" : "border-slate-200"
                    }`}
                  />
                  {fieldErrors.student_email && (
                    <p className="text-rose-600 text-xs mt-1 font-medium">{fieldErrors.student_email}</p>
                  )}
                </div>
              </div>

              <div>
                <input
                  type="text"
                  placeholder="Nama Lengkap Mahasiswa"
                  value={formData.student_name || ""}
                  onChange={(e) => {
                    setFormData({ ...formData, student_name: e.target.value });
                    setFieldErrors({ ...fieldErrors, student_name: "" });
                  }}
                  className={`border p-2.5 rounded-xl text-sm w-full focus:outline-none ${
                    fieldErrors.student_name ? "border-rose-500 bg-rose-50/30" : "border-slate-200"
                  }`}
                />
                {fieldErrors.student_name && (
                  <p className="text-rose-600 text-xs mt-1 font-medium">{fieldErrors.student_name}</p>
                )}
              </div>

              <div className="text-xs font-semibold tracking-wider text-blue-600 uppercase pt-2">
                Data Mata Kuliah
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <input
                    type="text"
                    placeholder="Kode MK (cth: IF101)"
                    value={formData.course_code || ""}
                    onChange={(e) => {
                      setFormData({ ...formData, course_code: e.target.value.toUpperCase() });
                      setFieldErrors({ ...fieldErrors, course_code: "" });
                    }}
                    className={`border p-2.5 rounded-xl text-sm w-full focus:outline-none ${
                      fieldErrors.course_code ? "border-rose-500 bg-rose-50/30" : "border-slate-200"
                    }`}
                  />
                  {fieldErrors.course_code && (
                    <p className="text-rose-600 text-xs mt-1 font-medium">{fieldErrors.course_code}</p>
                  )}
                </div>

                <div>
                  <input
                    type="number"
                    placeholder="SKS (1-6)"
                    value={formData.course_credits || ""}
                    onChange={(e) => {
                      setFormData({ ...formData, course_credits: Number(e.target.value) });
                      setFieldErrors({ ...fieldErrors, course_credits: "" });
                    }}
                    className={`border p-2.5 rounded-xl text-sm w-full focus:outline-none ${
                      fieldErrors.course_credits ? "border-rose-500 bg-rose-50/30" : "border-slate-200"
                    }`}
                  />
                  {fieldErrors.course_credits && (
                    <p className="text-rose-600 text-xs mt-1 font-medium">{fieldErrors.course_credits}</p>
                  )}
                </div>
              </div>

              <div>
                <input
                  type="text"
                  placeholder="Nama Mata Kuliah"
                  value={formData.course_name || ""}
                  onChange={(e) => {
                    setFormData({ ...formData, course_name: e.target.value });
                    setFieldErrors({ ...fieldErrors, course_name: "" });
                  }}
                  className={`border p-2.5 rounded-xl text-sm w-full focus:outline-none ${
                    fieldErrors.course_name ? "border-rose-500 bg-rose-50/30" : "border-slate-200"
                  }`}
                />
                {fieldErrors.course_name && (
                  <p className="text-rose-600 text-xs mt-1 font-medium">{fieldErrors.course_name}</p>
                )}
              </div>
            </>
          )}

          <div className="text-xs font-semibold tracking-wider text-blue-600 uppercase pt-2">
            Detail Enrollment
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <input
                type="text"
                placeholder="Tahun (2025/2026)"
                value={formData.academic_year || ""}
                onChange={(e) => {
                  setFormData({ ...formData, academic_year: e.target.value });
                  setFieldErrors({ ...fieldErrors, academic_year: "" });
                }}
                className={`border p-2.5 rounded-xl text-sm w-full focus:outline-none ${
                  fieldErrors.academic_year ? "border-rose-500 bg-rose-50/30" : "border-slate-200"
                }`}
              />
              {fieldErrors.academic_year && (
                <p className="text-rose-600 text-xs mt-1 font-medium">{fieldErrors.academic_year}</p>
              )}
            </div>

            <div>
              <select
                value={formData.semester || "GANJIL"}
                onChange={(e) => setFormData({ ...formData, semester: e.target.value })}
                className="border border-slate-200 p-2.5 rounded-xl text-sm bg-white w-full focus:outline-none"
              >
                <option value="GANJIL">GANJIL</option>
                <option value="GENAP">GENAP</option>
              </select>
            </div>

            <div>
              <select
                value={formData.status || "DRAFT"}
                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                className="border border-slate-200 p-2.5 rounded-xl text-sm bg-white w-full focus:outline-none"
              >
                <option value="DRAFT">DRAFT</option>
                <option value="SUBMITTED">SUBMITTED</option>
                <option value="APPROVED">APPROVED</option>
                <option value="REJECTED">REJECTED</option>
              </select>
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              onClick={onClose}
              className="border border-slate-200 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-slate-50 transition-all"
            >
              Batal
            </button>
            <button
              type="submit"
              className="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20"
            >
              Simpan Data
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}