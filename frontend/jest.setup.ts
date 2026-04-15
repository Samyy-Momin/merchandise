import "@testing-library/jest-dom";

// Mock localStorage for test environment
const storage: Record<string, string> = {};
const localStorageMock: Storage = {
  getItem: jest.fn((key: string) => storage[key] ?? null),
  setItem: jest.fn((key: string, value: string) => {
    storage[key] = value;
  }),
  removeItem: jest.fn((key: string) => {
    delete storage[key];
  }),
  clear: jest.fn(() => {
    Object.keys(storage).forEach((key) => delete storage[key]);
  }),
  get length() {
    return Object.keys(storage).length;
  },
  key: jest.fn((index: number) => Object.keys(storage)[index] ?? null),
};

Object.defineProperty(window, "localStorage", { value: localStorageMock });

// Silence console.log/debug in tests
jest.spyOn(console, "log").mockImplementation(() => {});
jest.spyOn(console, "debug").mockImplementation(() => {});
