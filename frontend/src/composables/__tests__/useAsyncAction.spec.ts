import { describe, it, expect, vi } from "vitest";
import { useAsyncAction } from "../useAsyncAction";

describe("useAsyncAction", () => {
  it("should initialize with correct default state", () => {
    const action = vi.fn().mockResolvedValue("success");
    const { loading, error, data } = useAsyncAction(action);

    expect(loading.value).toBe(false);
    expect(error.value).toBeNull();
    expect(data.value).toBeNull();
  });

  it("should execute action successfully", async () => {
    const mockResult = { id: 1, name: "Test" };
    const action = vi.fn().mockResolvedValue(mockResult);
    const { loading, error, data, execute } = useAsyncAction(action);

    expect(loading.value).toBe(false);

    const promise = execute();

    expect(loading.value).toBe(true);

    const result = await promise;

    expect(loading.value).toBe(false);
    expect(error.value).toBeNull();
    expect(data.value).toEqual(mockResult);
    expect(result).toEqual(mockResult);
    expect(action).toHaveBeenCalledTimes(1);
  });

  it("should handle action failure", async () => {
    const mockError = new Error("Action failed");
    const action = vi.fn().mockRejectedValue(mockError);
    const { loading, error, data, execute } = useAsyncAction(action);

    try {
      await execute();
    } catch (err) {
      expect(err).toBe(mockError);
    }

    expect(loading.value).toBe(false);
    expect(error.value).toBe(mockError);
    expect(data.value).toBeNull();
  });

  it("should call onSuccess callback when action succeeds", async () => {
    const mockResult = { id: 1, name: "Test" };
    const action = vi.fn().mockResolvedValue(mockResult);
    const onSuccess = vi.fn();
    const { execute } = useAsyncAction(action, { onSuccess });

    await execute();

    expect(onSuccess).toHaveBeenCalledWith(mockResult);
    expect(onSuccess).toHaveBeenCalledTimes(1);
  });

  it("should call onError callback when action fails", async () => {
    const mockError = new Error("Action failed");
    const action = vi.fn().mockRejectedValue(mockError);
    const onError = vi.fn();
    const { execute } = useAsyncAction(action, { onError });

    try {
      await execute();
    } catch (err) {
      // Expected to throw
    }

    expect(onError).toHaveBeenCalledWith(mockError);
    expect(onError).toHaveBeenCalledTimes(1);
  });

  it("should reset error when executing again", async () => {
    const action = vi
      .fn()
      .mockRejectedValueOnce(new Error("First error"))
      .mockResolvedValueOnce("success");

    const { error, execute } = useAsyncAction(action);

    try {
      await execute();
    } catch (err) {
      // First call fails
    }

    expect(error.value).not.toBeNull();

    await execute();

    expect(error.value).toBeNull();
  });

  it("should handle multiple sequential executions", async () => {
    let counter = 0;
    const action = vi.fn().mockImplementation(async () => {
      counter++;
      return counter;
    });

    const { data, execute } = useAsyncAction(action);

    await execute();
    expect(data.value).toBe(1);

    await execute();
    expect(data.value).toBe(2);

    await execute();
    expect(data.value).toBe(3);

    expect(action).toHaveBeenCalledTimes(3);
  });

  it("should maintain loading state correctly during execution", async () => {
    const action = vi.fn().mockImplementation(
      () =>
        new Promise((resolve) => {
          setTimeout(() => resolve("done"), 100);
        })
    );

    const { loading, execute } = useAsyncAction(action);

    expect(loading.value).toBe(false);

    const promise = execute();
    expect(loading.value).toBe(true);

    await promise;
    expect(loading.value).toBe(false);
  });
});
