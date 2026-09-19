"use client";

import { useState, useEffect } from "react";
import { FilterPayload, SortPayload, FilterCondition, SortOrder } from "../hooks/useAdvancedQuery";

interface AdvancedQueryModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentFilter: FilterPayload;
  currentSort: SortPayload;
  onApply: (filter: FilterPayload, sort: SortPayload) => void;
}

const AVAILABLE_COLUMNS = [
  { value: "student_nim", label: "NIM Mahasiswa" },
  { value: "academic_year", label: "Tahun Akademik" },
  { value: "semester", label: "Semester" },
  { value: "status", label: "Status" },
  { value: "course_code", label: "Kode Mata Kuliah" },
];

const OPERATORS_MAP: Record<string, { value: string; label: string }[]> = {
  student_nim: [
    { value: "contains", label: "Contains" },
    { value: "startsWith", label: "Starts With" },
    { value: "equal", label: "Equal" },
  ],
  academic_year: [
    { value: "equal", label: "Equal" },
    { value: "between", label: "Between (Rentang)" },
  ],
  semester: [
    { value: "in", label: "In (Pilih Banyak)" },
  ],
  status: [
    { value: "in", label: "In (Pilih Banyak)" },
  ],
  course_code: [
    { value: "contains", label: "Contains" },
  ],
};

export default function AdvancedQueryModal({
  isOpen,
  onClose,
  currentFilter,
  currentSort,
  onApply,
}: AdvancedQueryModalProps) {
    console.log("Status modal isOpen saat ini:", isOpen);
  const [logic, setLogic] = useState<"AND" | "OR">(currentFilter.logic || "AND");
  const [conditions, setConditions] = useState<FilterCondition[]>(currentFilter.conditions || []);
  const [orders, setOrders] = useState<SortOrder[]>(currentSort.orders || []);

  useEffect(() => {
    if (isOpen) {
      setLogic(currentFilter.logic || "AND");
      setConditions(currentFilter.conditions ? JSON.parse(JSON.stringify(currentFilter.conditions)) : []);
      setOrders(currentSort.orders ? JSON.parse(JSON.stringify(currentSort.orders)) : []);
    }
  }, [isOpen, currentFilter, currentSort]);

  if (!isOpen) return null;

  // Handler Filter
  const addCondition = () => {
    setConditions([...conditions, { field: "student_nim", op: "contains", value: "" }]);
  };

  const removeCondition = (index: number) => {
    setConditions(conditions.filter((_, i) => i !== index));
  };

  const updateCondition = (index: number, key: keyof FilterCondition, val: any) => {
    const newConditions = [...conditions];
    newConditions[index][key] = val;
    // Reset value jika field berubah agar operator & nilai menyesuaikan
    if (key === "field") {
      const defaultOp = OPERATORS_MAP[val]?.[0]?.value || "equal";
      newConditions[index].op = defaultOp;
      newConditions[index].value = defaultOp === "between" ? ["", ""] : "";
    }
    setConditions(newConditions);
  };

  // Handler Sort
  const addOrder = () => {
    setOrders([...orders, { field: "academic_year", direction: "desc" }]);
  };

  const removeOrder = (index: number) => {
    setOrders(orders.filter((_, i) => i !== index));
  };

  const updateOrder = (index: number, key: keyof SortOrder, val: any) => {
    const newOrders = [...orders];
    newOrders[index][key] = val;
    setOrders(newOrders);
  };

  const handleSave = () => {
    onApply(
      { logic, conditions },
      { orders }
    );
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
        {/* Header */}
        <div className="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h3 className="font-semibold text-slate-800 text-lg">Advanced Filter & Multi-Sort Builder</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
        </div>

        {/* Body Content */}
        <div className="p-6 overflow-y-auto space-y-6 flex-1 text-sm">
          {/* Bagian 1: Advanced Filter */}
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <h4 className="font-semibold text-slate-700">1. Filter Kondisi</h4>
              <div className="flex items-center gap-3">
                <span className="text-xs text-slate-500 font-medium">Logika Antar Filter:</span>
                <label className="inline-flex items-center gap-1 cursor-pointer">
                  <input type="radio" name="logic" value="AND" checked={logic === "AND"} onChange={() => setLogic("AND")} />
                  <span className="font-medium text-xs">AND</span>
                </label>
                <label className="inline-flex items-center gap-1 cursor-pointer">
                  <input type="radio" name="logic" value="OR" checked={logic === "OR"} onChange={() => setLogic("OR")} />
                  <span className="font-medium text-xs">OR</span>
                </label>
              </div>
            </div>

            {conditions.length === 0 ? (
              <p className="text-slate-400 italic text-xs">Belum ada filter ditambahkan.</p>
            ) : (
              <div className="space-y-3">
                {conditions.map((cond, idx) => {
                  const availableOps = OPERATORS_MAP[cond.field] || [{ value: "equal", label: "Equal" }];
                  return (
                    <div key={idx} className="flex items-center gap-2 bg-slate-50 p-3 rounded-xl border border-slate-100">
                      {/* Kolom Select */}
                      <select
                        value={cond.field}
                        onChange={(e) => updateCondition(idx, "field", e.target.value)}
                        className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20"
                      >
                        {AVAILABLE_COLUMNS.map((col) => (
                          <option key={col.value} value={col.value}>{col.label}</option>
                        ))}
                      </select>

                      {/* Operator Select */}
                      <select
                        value={cond.op}
                        onChange={(e) => updateCondition(idx, "op", e.target.value)}
                        className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20"
                      >
                        {availableOps.map((op) => (
                          <option key={op.value} value={op.value}>{op.label}</option>
                        ))}
                      </select>

                      {/* Input Nilai Berdasarkan Operator */}
                      {cond.op === "between" ? (
                        <div className="flex items-center gap-1 flex-1">
                          <input
                            type="text"
                            placeholder="Mulai (Cth: 2023/2024)"
                            value={Array.isArray(cond.value) ? cond.value[0] || "" : ""}
                            onChange={(e) => {
                              const valArr = Array.isArray(cond.value) ? [...cond.value] : ["", ""];
                              valArr[0] = e.target.value;
                              updateCondition(idx, "value", valArr);
                            }}
                            className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs w-full"
                          />
                          <span className="text-slate-400 text-xs">s/d</span>
                          <input
                            type="text"
                            placeholder="Akhir (Cth: 2025/2026)"
                            value={Array.isArray(cond.value) ? cond.value[1] || "" : ""}
                            onChange={(e) => {
                              const valArr = Array.isArray(cond.value) ? [...cond.value] : ["", ""];
                              valArr[1] = e.target.value;
                              updateCondition(idx, "value", valArr);
                            }}
                            className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs w-full"
                          />
                        </div>
                      ) : cond.op === "in" ? (
                        <input
                          type="text"
                          placeholder="Pisahkan koma (Cth: GANJIL,GENAP)"
                          value={Array.isArray(cond.value) ? cond.value.join(",") : cond.value || ""}
                          onChange={(e) => {
                            const arr = e.target.value.split(",").map((s) => s.trim());
                            updateCondition(idx, "value", arr);
                          }}
                          className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs flex-1"
                        />
                      ) : (
                        <input
                          type="text"
                          placeholder="Nilai pencarian..."
                          value={cond.value || ""}
                          onChange={(e) => updateCondition(idx, "value", e.target.value)}
                          className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs flex-1"
                        />
                      )}

                      {/* Tombol Hapus Baris */}
                      <button
                        onClick={() => removeCondition(idx)}
                        className="text-red-500 hover:text-red-700 px-2 py-1 font-bold text-sm"
                        title="Hapus baris"
                      >
                        &times;
                      </button>
                    </div>
                  );
                })}
              </div>
            )}

            <button
              onClick={addCondition}
              className="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all"
            >
              + Tambah Filter
            </button>
          </div>

          <hr className="border-slate-100" />

          {/* Bagian 2: Advanced Order / Multi-Sort */}
          <div className="space-y-4">
            <h4 className="font-semibold text-slate-700">2. Multi-Column Sorting (Advanced Order)</h4>

            {orders.length === 0 ? (
              <p className="text-slate-400 italic text-xs">Belum ada aturan sorting diatur (Default: ID Desc).</p>
            ) : (
              <div className="space-y-3">
                {orders.map((ord, idx) => (
                  <div key={idx} className="flex items-center gap-2 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span className="text-xs text-slate-400 font-medium">Prioritas #{idx + 1}</span>
                    <select
                      value={ord.field}
                      onChange={(e) => updateOrder(idx, "field", e.target.value)}
                      className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 flex-1"
                    >
                      {AVAILABLE_COLUMNS.map((col) => (
                        <option key={col.value} value={col.value}>{col.label}</option>
                      ))}
                    </select>

                    <select
                      value={ord.direction}
                      onChange={(e) => updateOrder(idx, "direction", e.target.value as "asc" | "desc")}
                      className="border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20"
                    >
                      <option value="asc">Ascending (ASC)</option>
                      <option value="desc">Descending (DESC)</option>
                    </select>

                    <button
                      onClick={() => removeOrder(idx)}
                      className="text-red-500 hover:text-red-700 px-2 py-1 font-bold text-sm"
                      title="Hapus baris"
                    >
                      &times;
                    </button>
                  </div>
                ))}
              </div>
            )}

            <button
              onClick={addOrder}
              className="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all"
            >
              + Tambah Urutan Sort
            </button>
          </div>
        </div>

        {/* Footer */}
        <div className="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/50">
          <button
            onClick={onClose}
            className="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-medium transition-all"
          >
            Batal
          </button>
          <button
            onClick={handleSave}
            className="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium shadow-sm shadow-blue-600/20 transition-all"
          >
            Terapkan Filter & Sort
          </button>
        </div>
      </div>
    </div>
  );
}