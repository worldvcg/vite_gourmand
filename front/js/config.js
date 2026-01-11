export const API_BASE =
  (location.hostname === "localhost" || location.hostname === "127.0.0.1")
    ? "http://localhost:9000/index.php?route="
    : "/api/back/public/index.php?route=";