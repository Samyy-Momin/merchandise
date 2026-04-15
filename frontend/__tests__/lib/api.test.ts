/**
 * Tests for the apiFetch utility and getCategoriesCached.
 */

// Must mock keycloak BEFORE importing api module
jest.mock("@/lib/keycloak", () => ({
  getToken: jest.fn(() => "fake-jwt-token"),
}));

import { apiFetch, getCategoriesCached } from "@/lib/api";

// Mock global fetch
const mockFetch = jest.fn();
global.fetch = mockFetch;

beforeEach(() => {
  mockFetch.mockReset();
});

describe("apiFetch (lib/api.ts)", () => {
  test("throws if path is empty", async () => {
    await expect(apiFetch("")).rejects.toThrow("apiFetch: path is required");
  });

  test("adds Bearer token from keycloak", async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      json: () => Promise.resolve({ data: [] }),
    });

    await apiFetch("/api/products");

    expect(mockFetch).toHaveBeenCalledTimes(1);
    const [, opts] = mockFetch.mock.calls[0];
    expect(opts.headers.Authorization).toBe("Bearer fake-jwt-token");
  });

  test("prepends API_BASE to path", async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      json: () => Promise.resolve([]),
    });

    await apiFetch("/api/categories");

    const [url] = mockFetch.mock.calls[0];
    expect(url).toContain("/api/categories");
  });

  test("handles path without leading slash", async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      json: () => Promise.resolve({ ok: true }),
    });

    await apiFetch("api/me");

    const [url] = mockFetch.mock.calls[0];
    expect(url).toContain("/api/me");
  });

  test("throws on non-ok response", async () => {
    mockFetch.mockResolvedValue({
      ok: false,
      status: 403,
      text: () => Promise.resolve("Forbidden"),
    });

    await expect(apiFetch("/api/admin")).rejects.toThrow("API 403: Forbidden");
  });

  test("returns JSON for application/json", async () => {
    const data = { user: { sub: "123" }, roles: ["buyer"] };
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      json: () => Promise.resolve(data),
    });

    const result = await apiFetch("/api/me");
    expect(result).toEqual(data);
  });

  test("returns text for non-JSON content type", async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "text/plain" }),
      text: () => Promise.resolve("hello"),
    });

    const result = await apiFetch("/api/health");
    expect(result).toBe("hello");
  });

  test("passes cache: no-store", async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      json: () => Promise.resolve({}),
    });

    await apiFetch("/api/me");
    const [, opts] = mockFetch.mock.calls[0];
    expect(opts.cache).toBe("no-store");
  });

  test("passes Accept: application/json header", async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      json: () => Promise.resolve({}),
    });

    await apiFetch("/api/me");
    const [, opts] = mockFetch.mock.calls[0];
    expect(opts.headers.Accept).toBe("application/json");
  });
});

describe("getCategoriesCached", () => {
  test("fetches categories and caches them", async () => {
    const cats = [{ id: 1, name: "Stationery", slug: "stationery" }];
    mockFetch.mockResolvedValue({
      ok: true,
      headers: new Headers({ "content-type": "application/json" }),
      json: () => Promise.resolve(cats),
    });

    const result1 = await getCategoriesCached();
    expect(result1).toEqual(cats);

    // Second call should NOT trigger another fetch (cached)
    const result2 = await getCategoriesCached();
    expect(result2).toEqual(cats);
    expect(mockFetch).toHaveBeenCalledTimes(1);
  });
});
