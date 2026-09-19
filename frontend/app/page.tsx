"use client";

import { useState, useEffect } from "react";
import {
  useAdvancedQuery,
  FilterPayload,
  SortPayload,
} from "./enrollments/hooks/useAdvancedQuery";
import { Enrollment, Meta } from "./enrollments/type";
import IntegratedHeaderToolbar from "./enrollments/components/IntegratedHeaderToolbar";
import EnrollmentTable from "./enrollments/components/EnrollmentTable";
import EnrollmentModal from "./enrollments/components/EnrollmentModal";
import AdvancedQueryModal from "./enrollments/components/AdvancedQueryModal";

export default function HomePage() {
  const { payload, setFilter, setSort, setSearch, setPage, setPageSize } =
    useAdvancedQuery();

  const [data, setData] = useState<Enrollment[]>([]);
  const [loading, setLoading] = useState(true);
  const [isAdvancedModalOpen, setIsAdvancedModalOpen] = useState(false);
  const [isMounted, setIsMounted] = useState(false);

  useEffect(() => {
    setIsMounted(true);
  }, []);

  const [meta, setMeta] = useState<Meta>({
    total: 0,
    current_page: 1,
    query_time_ms: 0,
  });

  // Modal Create/Edit State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    student_nim: "",
    student_name: "",
    student_email: "",
    course_code: "",
    course_name: "",
    course_credits: 3,
    academic_year: "2025/2026",
    semester: "GANJIL",
    status: "DRAFT",
  });
  const [errorMessage, setErrorMessage] = useState("");

  const fetchData = async () => {
    setLoading(true);
    try {
      const baseUrl =
        process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";
      const url = new URL(`${baseUrl}/enrollments`);

      if (payload.search) {
        url.searchParams.set("search", payload.search);
      }

      if (payload.filter && payload.filter.conditions.length > 0) {
        url.searchParams.set("filter", JSON.stringify(payload.filter));
      }
      if (payload.sort && payload.sort.orders.length > 0) {
        url.searchParams.set("sort", JSON.stringify(payload.sort));
      }
      url.searchParams.set("page", String(payload.page));
      url.searchParams.set("pageSize", String(payload.pageSize));

      const res = await fetch(url.toString());
      const json = await res.json();

      setData(json.data || []);
      setMeta(json.meta || { total: 0, current_page: 1, query_time_ms: 0 });
    } catch (error) {
      console.error("Gagal mengambil data dari Laravel:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [payload]);

  // Handler Sorting per Header Kolom
  const handleSort = (field: string) => {
    const activeOrder = payload.sort.orders.find((o) => o.field === field);
    let nextDir: "asc" | "desc" = "asc";

    if (activeOrder) {
      if (activeOrder.dir === "asc") {
        nextDir = "desc";
      } else {
        // Jika sudah desc, hapus/reset sort untuk kolom ini atau ganti ke asc
        nextDir = "asc";
      }
    }

    setSort({
      orders: [{ field, dir: nextDir }],
    });
    setPage(1);
  };

  const handleQuickFilterChange = (type: "status" | "semester", value: string) => {
    const currentConditions = payload.filter.conditions.filter(
      (c) => c.field !== type
    );

    if (value) {
      currentConditions.push({
        field: type,
        operator: "=",
        value: value,
      });
    }

    setFilter({
      conjunction: payload.filter.conjunction || "AND",
      conditions: currentConditions,
    });
    setPage(1);
  };

  const handleExport = () => {
    const url = new URL(`${window.location.origin}/api/enrollments/export`);
    if (payload.search) {
      url.searchParams.set("search", payload.search);
    }
    if (payload.filter && payload.filter.conditions.length > 0) {
      url.searchParams.set("filter", JSON.stringify(payload.filter));
    }
    if (payload.sort && payload.sort.orders.length > 0) {
      url.searchParams.set("sort", JSON.stringify(payload.sort));
    }

    const a = document.createElement("a");
    a.href = url.toString();
    a.download = `enrollments_${Date.now()}.csv`;
    a.click();
  };

  const handleApplyAdvancedQuery = (
    newFilter: FilterPayload,
    newSort: SortPayload,
  ) => {
    setFilter(newFilter);
    setSort(newSort);
    setPage(1);
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Apakah Anda yakin ingin menghapus data KRS ini?")) return;
    try {
      const res = await fetch(`/api/enrollments/${id}`, { method: "DELETE" });
      if (res.ok) fetchData();
      else alert("Gagal menghapus data.");
    } catch (err) {
      console.error(err);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage("");

    const url = editingId
      ? `/api/enrollments/${editingId}`
      : "/api/enrollments";
    const method = editingId ? "PUT" : "POST";

    try {
      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          student_mode: "new",
          student: {
            nim: formData.student_nim,
            name: formData.student_name,
            email: formData.student_email,
          },
          course_mode: "new",
          course: {
            code: formData.course_code,
            name: formData.course_name,
            credits: Number(formData.course_credits),
          },
          academic_year: formData.academic_year,
          semester: formData.semester,
          status: formData.status,
        }),
      });

      const json = await res.json();
      if (!res.ok) {
        setErrorMessage(
          json.message || "Terjadi kesalahan pada validasi server.",
        );
        return;
      }

      setIsModalOpen(false);
      setEditingId(null);
      fetchData();
    } catch (err) {
      setErrorMessage("Koneksi ke server gagal.");
    }
  };

  const openCreateModal = () => {
    setEditingId(null);
    setFormData({
      student_nim: "",
      student_name: "",
      student_email: "",
      course_code: "",
      course_name: "",
      course_credits: 3,
      academic_year: "2025/2026",
      semester: "GANJIL",
      status: "DRAFT",
    });
    setErrorMessage("");
    setIsModalOpen(true);
  };

  const openEditModal = (item: Enrollment) => {
    setEditingId(item.id);
    setFormData({
      student_nim: item.student_nim,
      student_name: item.student_name,
      student_email: "",
      course_code: item.course_code,
      course_name: item.course_name,
      course_credits: 3,
      academic_year: item.academic_year,
      semester: item.semester,
      status: item.status,
    });
    setErrorMessage("");
    setIsModalOpen(true);
  };

  const activeStatus =
    payload.filter.conditions.find((c) => c.field === "status")?.value || "";
  const activeSemester =
    payload.filter.conditions.find((c) => c.field === "semester")?.value || "";

  const currentSortOrder = payload.sort.orders[0];

  const hasActiveFilters =
    Boolean(payload.search) ||
    payload.filter.conditions.length > 0 ||
    payload.sort.orders.length > 0;

  return (
    <div className="min-h-screen bg-slate-50/50 py-8 px-4 sm:px-6 lg:px-8 text-slate-800">
      <div className="max-w-7xl mx-auto space-y-6">
        <IntegratedHeaderToolbar
          onOpenCreate={openCreateModal}
          onOpenAdvancedModal={() => setIsAdvancedModalOpen(true)}
          onExport={handleExport}
          onSearchChange={(value) => setSearch(value)}
          onQuickFilterChange={handleQuickFilterChange}
          selectedStatus={activeStatus}
          selectedSemester={activeSemester}
          hasActiveFilters={hasActiveFilters}
        />

        <EnrollmentTable
          data={data}
          meta={meta}
          loading={loading}
          isMounted={isMounted}
          page={payload.page}
          pageSize={payload.pageSize}
          setPage={setPage}
          setPageSize={setPageSize}
          sortField={currentSortOrder?.field || ""}
          sortDir={currentSortOrder?.dir || "asc"}
          onSort={handleSort}
          onEdit={openEditModal}
          onDelete={handleDelete}
        />

        {isMounted && (
          <AdvancedQueryModal
            isOpen={isAdvancedModalOpen}
            onClose={() => setIsAdvancedModalOpen(false)}
            currentFilter={payload.filter}
            currentSort={payload.sort}
            onApply={handleApplyAdvancedQuery}
          />
        )}

        {isMounted && (
          <EnrollmentModal
            isOpen={isModalOpen}
            onClose={() => setIsModalOpen(false)}
            editingId={editingId}
            formData={formData}
            setFormData={setFormData}
            errorMessage={errorMessage}
            onSave={handleSave}
          />
        )}
      </div>
    </div>
  );
}