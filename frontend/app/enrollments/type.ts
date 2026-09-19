export interface Enrollment {
  id: number;
  student_id: number;
  course_id: number;
  student_nim: string;
  student_name: string;
  course_code: string;
  course_name: string;
  academic_year: string;
  semester: string;
  status: string;
}

export interface Meta {
  total: number;
  current_page: number;
  last_page?: number;
  query_time_ms: number;
}