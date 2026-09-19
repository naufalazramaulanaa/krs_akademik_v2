"use client";

import { useState, useEffect, useRef } from "react";

interface IntegratedHeaderToolbarProps {
  onOpenCreate: () => void;
  onOpenAdvancedModal: () => void;
  onExport: () => void;
  onSearchChange: (value: string) => void;
  onQuickFilterChange: (type: "status" | "semester", value: string) => void;
  selectedStatus: string;
  selectedSemester: string;
  hasActiveFilters: boolean;
}

export default function IntegratedHeaderToolbar({
  onOpenCreate,
  onOpenAdvancedModal,
  onExport,
  onSearchChange,
  onQuickFilterChange,
  selectedStatus,
  selectedSemester,
  hasActiveFilters,
}: IntegratedHeaderToolbarProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const isFirstRender = useRef(true);

  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false;
      return;
    }

    const handler = setTimeout(() => {
      onSearchChange(searchTerm);
    }, 300);

    return () => clearTimeout(handler);
  }, [searchTerm]);

  return (
    <div className="bg-white rounded-xl border border-slate-200/80 p-6 shadow-sm space-y-6">
      {/* Baris 1: Header + Action Utama */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
            Manajemen KRS Akademik
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Optimasi Performa Skala 5 Juta Data dengan Service-Action Pattern
          </p>
        </div>

        <button
          onClick={onOpenCreate}
          className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm transition-all cursor-pointer"
        >
          <span>+ Tambah KRS Baru</span>
        </button>
      </div>

      {/* Baris 2: Live Search & Quick Filters & Advanced Tools */}
      <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div className="flex flex-wrap items-center gap-3 w-full lg:w-auto">
          {/* Live Search Input */}
          <div className="relative w-full sm:w-72">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Cari NIM, Nama, Kode..."
              className="w-full pl-9 pr-8 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-800 placeholder-slate-400 transition-all"
            />
            {searchTerm && (
              <button
                onClick={() => setSearchTerm("")}
                className="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            )}
          </div>

          {/* Quick Filter: Status */}
          <select
            value={selectedStatus}
            onChange={(e) => onQuickFilterChange("status", e.target.value)}
            className="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
          >
            <option value="">Semua Status</option>
            <option value="DRAFT">Draft</option>
            <option value="SUBMITTED">Submitted</option>
            <option value="APPROVED">Approved</option>
            <option value="REJECTED">Rejected</option>
          </select>

          {/* Quick Filter: Semester */}
          <select
            value={selectedSemester}
            onChange={(e) => onQuickFilterChange("semester", e.target.value)}
            className="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
          >
            <option value="">Semua Semester</option>
            <option value="GANJIL">Ganjil</option>
            <option value="GENAP">Genap</option>
          </select>
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-3">
          <button
            onClick={onOpenAdvancedModal}
            className={`inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium rounded-lg border transition-all cursor-pointer ${
              hasActiveFilters
                ? "bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100"
                : "bg-white text-slate-700 border-slate-200 hover:bg-slate-50"
            }`}
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 00-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <span>Advanced Query</span>
            {hasActiveFilters && (
              <span className="px-1.5 py-0.5 text-[10px] font-semibold bg-blue-600 text-white rounded-full">
                Aktif
              </span>
            )}
          </button>

          <button
            onClick={onExport}
            className="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm transition-all cursor-pointer"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Export CSV</span>
          </button>
        </div>
      </div>
    </div>
  );
}