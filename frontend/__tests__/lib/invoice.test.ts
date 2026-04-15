/**
 * Tests for invoice download utilities.
 */

jest.mock("@/lib/keycloak", () => ({
  getToken: jest.fn(() => "test-token"),
}));

import { openInvoicePdf, downloadInvoiceExcel } from "@/lib/invoice";

const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock URL.createObjectURL and URL.revokeObjectURL
global.URL.createObjectURL = jest.fn(() => "blob:http://localhost/fake-blob");
global.URL.revokeObjectURL = jest.fn();

// Mock window.open
const mockOpen = jest.fn();
window.open = mockOpen;

beforeEach(() => {
  mockFetch.mockReset();
  mockOpen.mockReset();
});

describe("Invoice Utilities (lib/invoice.ts)", () => {
  describe("openInvoicePdf", () => {
    test("calls fetch with correct URL and Accept header", async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        blob: () => Promise.resolve(new Blob(["pdf-content"], { type: "application/pdf" })),
      });

      await openInvoicePdf(42);

      expect(mockFetch).toHaveBeenCalledTimes(1);
      const [url, opts] = mockFetch.mock.calls[0];
      expect(url).toContain("/api/invoices/42/pdf");
      expect(opts.headers.Accept).toBe("application/pdf");
      expect(opts.headers.Authorization).toBe("Bearer test-token");
    });

    test("opens PDF in new tab", async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        blob: () => Promise.resolve(new Blob()),
      });

      await openInvoicePdf(1);
      expect(mockOpen).toHaveBeenCalledWith("blob:http://localhost/fake-blob", "_blank");
    });

    test("throws on non-ok response", async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 404,
        text: () => Promise.resolve("Not found"),
      });

      await expect(openInvoicePdf(999)).rejects.toThrow("PDF 404: Not found");
    });
  });

  describe("downloadInvoiceExcel", () => {
    test("calls fetch with correct URL and Accept header", async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        blob: () => Promise.resolve(new Blob()),
      });

      // Mock document.createElement for the download link
      const mockLink = {
        href: "",
        download: "",
        click: jest.fn(),
        remove: jest.fn(),
      };
      jest.spyOn(document, "createElement").mockReturnValue(mockLink as any);
      jest.spyOn(document.body, "appendChild").mockImplementation(() => mockLink as any);

      await downloadInvoiceExcel(42);

      const [url, opts] = mockFetch.mock.calls[0];
      expect(url).toContain("/api/invoices/42/excel");
      expect(opts.headers.Accept).toContain("spreadsheetml");
      expect(mockLink.download).toBe("invoice-42.xlsx");
      expect(mockLink.click).toHaveBeenCalled();
    });

    test("throws on non-ok response", async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 500,
        text: () => Promise.resolve("Server error"),
      });

      await expect(downloadInvoiceExcel(1)).rejects.toThrow("Excel 500: Server error");
    });
  });
});
