import { useState, useMemo, useCallback } from "react";

export interface FilterCondition {
  field: string;
  op: "equals" | "contains" | "startsWith" | "between" | "in";
  value: any;
}

export interface FilterPayload {
  logic: "AND" | "OR";
  conditions: FilterCondition[];
}

export interface SortOrder {
  field: string;
  direction: "asc" | "desc";
}

export interface SortPayload {
  orders: SortOrder[];
}

export function useAdvancedQuery() {
  const [search, setSearchState] = useState<string>("");

  const [filter, setFilterState] = useState<FilterPayload>({
    logic: "AND",
    conditions: [],
  });

  const [sort, setSortState] = useState<SortPayload>({
    orders: [],
  });

  const [page, setPageState] = useState<number>(1);
  const [pageSize, setPageSizeState] = useState<number>(25);

  // 1. Wrapper setPage agar mendukung nilai angka MAUPUN callback (p => p + 1)
  const setPage = useCallback((value: number | ((prev: number) => number)) => {
    setPageState((prevPage) => {
      return typeof value === "function" ? value(prevPage) : value;
    });
  }, []);

  // 2. Custom setter dengan nama fungsi unik
  const setSearch = useCallback((val: string) => {
    setSearchState(val);
    setPageState(1);
  }, []);

  const setFilter = useCallback((newFilter: FilterPayload) => {
    setFilterState(newFilter);
    setPageState(1);
  }, []);

  const setSort = useCallback((newSort: SortPayload) => {
    setSortState(newSort);
    setPageState(1);
  }, []);

  const setPageSize = useCallback((size: number) => {
    setPageSizeState(size);
    setPageState(1);
  }, []);

  const payload = useMemo(
    () => ({
      search,
      filter,
      sort,
      page,
      pageSize,
    }),
    [search, filter, sort, page, pageSize]
  );

  const resetQuery = useCallback(() => {
    setSearchState("");
    setFilterState({ logic: "AND", conditions: [] });
    setSortState({ orders: [] });
    setPageState(1);
  }, []);

  return {
    payload,
    setSearch,
    setFilter,
    setSort,
    setPage,
    setPageSize,
    resetQuery,
  };
}