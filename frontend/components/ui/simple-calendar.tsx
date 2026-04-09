"use client";

import * as React from "react";

type Props = {
  selected?: Date | null;
  onSelect: (date: Date) => void;
  className?: string;
};

function daysInMonth(year: number, month: number) {
  return new Date(year, month + 1, 0).getDate();
}

function startWeekday(year: number, month: number) {
  return new Date(year, month, 1).getDay(); // 0=Sun
}

export function SimpleCalendar({ selected, onSelect, className }: Props) {
  const today = new Date();
  const [view, setView] = React.useState<{ y: number; m: number }>(() => ({ y: today.getFullYear(), m: today.getMonth() }));

  const y = view.y; const m = view.m;
  const dim = daysInMonth(y, m);
  const first = startWeekday(y, m);
  const cells: (Date | null)[] = [];
  for (let i = 0; i < first; i++) cells.push(null);
  for (let d = 1; d <= dim; d++) cells.push(new Date(y, m, d));
  while (cells.length % 7 !== 0) cells.push(null);

  const isSameDay = (a?: Date | null, b?: Date | null) => !!a && !!b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

  return (
    <div className={"border bg-background text-foreground p-2 " + (className || "")}
      role="dialog" aria-label="Calendar">
      <div className="flex items-center justify-between mb-2">
        <button className="border px-2 py-1 text-sm" onClick={() => setView((v) => ({ y: v.m === 0 ? v.y - 1 : v.y, m: v.m === 0 ? 11 : v.m - 1 }))}>{"<"}</button>
        <div className="text-sm font-medium">
          {new Date(y, m, 1).toLocaleString(undefined, { month: 'long', year: 'numeric' })}
        </div>
        <button className="border px-2 py-1 text-sm" onClick={() => setView((v) => ({ y: v.m === 11 ? v.y + 1 : v.y, m: v.m === 11 ? 0 : v.m + 1 }))}>{">"}</button>
      </div>
      <div className="grid grid-cols-7 gap-1 text-xs mb-1">
        {["Su","Mo","Tu","We","Th","Fr","Sa"].map((d) => (
          <div key={d} className="text-center text-muted-foreground">{d}</div>
        ))}
      </div>
      <div className="grid grid-cols-7 gap-1 text-sm">
        {cells.map((date, idx) => (
          <button
            key={idx}
            disabled={!date}
            className={
              "h-8 w-8 flex items-center justify-center border " +
              (date && isSameDay(date, selected) ? "bg-primary text-primary-foreground" : "")
            }
            onClick={() => date && onSelect(date)}
          >
            {date ? date.getDate() : ""}
          </button>
        ))}
      </div>
    </div>
  );
}

