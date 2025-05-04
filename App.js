import React, { useState, useRef } from "react";
import "./App.css";

function App() {
  const [cols, setCols] = useState(10);
  const [rows, setRows] = useState(10);
  const [grid, setGrid] = useState(() => makeGrid(10, 10));
  const [beginningGrid, setBeginningGrid] = useState(() => makeGrid(10, 10));
  const [running, setRunning] = useState(false);
  const [generation, setGeneration] = useState(0);
  const runningRef = useRef(running);
  runningRef.current = running;

  // Helper to make an empty grid
  function makeGrid(rows, cols) {
    return Array.from({ length: rows }, () => Array(cols).fill(0));
  }

  const saveGrid = async (name) => {
    const gridString = JSON.stringify({ grid: beginningGrid }); // Wrap inside { grid: ... }
    console.log("saveGrid: Saving with name:", name);
    console.log("saveGrid: Sending data:", gridString);
    try {
      const response = await fetch(
        `https://codd.cs.gsu.edu/~bwatts12/Project_Work/ProjectHTML3/save_load.php?name=${encodeURIComponent(name)}`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: gridString, // Send as JSON with { grid: [...] }
        }
      );
      console.log("saveGrid: Response Status:", response.status);
      console.log("saveGrid: Full Response:", response);
      const data = await response.json();
      console.log("saveGrid: Response Data:", data);
      if (data.success) {
        console.log("Grid saved successfully");
      } else {
        console.error("Save failed", data.message || "Unknown error");
      }
    } catch (error) {
      console.error("Error saving grid:", error);
    }
  };
  
  

  const loadGrid = async (name) => {
    try {
      runningRef.current = false;
      setRunning(false);
  
      const response = await fetch(
        `https://codd.cs.gsu.edu/~bwatts12/Project_Work/ProjectHTML3/save_load.php?name=${encodeURIComponent(name)}`,
        { method: "GET" }
      );
  
      const data = await response.json();
      console.log("Received data:", data);
  
      if (data.success && Array.isArray(data.grid)) {
        const loadedGrid = data.grid;
  
        const loadedRows = loadedGrid.length;
        const loadedCols = loadedGrid[0]?.length || 0;
        setRows(loadedRows);
        setCols(loadedCols);
        setBeginningGrid(loadedGrid);
        setGrid(loadedGrid);
        setGeneration(0);
      } else {
        console.error("Load failed:", data.message || "Grid not found or malformed data");
        alert("Error: Grid not found or invalid data.");
      }
    } catch (error) {
      console.error("Error loading grid:", error);
      alert("Error loading grid: " + error.message);
    }
  };  

  // Check number of live neighbors
  const check = (grid, x, y) => {
    let neighbors = 0;
    for (let dx = -1; dx <= 1; dx++) {
      for (let dy = -1; dy <= 1; dy++) {
        if (dx === 0 && dy === 0) continue;
        const newX = x + dx;
        const newY = y + dy;
        if (newX >= 0 && newX < rows && newY >= 0 && newY < cols) {
          neighbors += grid[newX][newY];
        }
      }
    }
    return neighbors;
  };

  // Move to next generation
  const nextGen = () => {
    setGrid((g) => {
      const newGrid = makeGrid(rows, cols);
      for (let i = 0; i < rows; i++) {
        for (let j = 0; j < cols; j++) {
          const neighbors = check(g, i, j);
          if (g[i][j] === 1) {
            newGrid[i][j] = neighbors === 2 || neighbors === 3 ? 1 : 0;
          } else {
            newGrid[i][j] = neighbors === 3 ? 1 : 0;
          }
        }
      }
      return newGrid;
    });
    setGeneration((gen) => gen + 1);
  };

  // Handle click on cell
  const handleClick = (i, j, isBeginningGrid) => {
    if (running && !isBeginningGrid) return;
    const newGrid = (isBeginningGrid ? beginningGrid : grid).map((row, rowIndex) =>
      row.map((col, colIndex) =>
        rowIndex === i && colIndex === j ? (col ? 0 : 1) : col
      )
    );
    if (isBeginningGrid) {
      setBeginningGrid(newGrid);
    } else {
      setGrid(newGrid);
    }
  };

  // Run simulation
  const runSimulation = () => {
    if (!runningRef.current) return;
    nextGen();
    setTimeout(runSimulation, 500);
  };

  // Handle resize
  const handleResize = () => {
    const newRows = parseInt(document.getElementById("rowsInput").value);
    const newCols = parseInt(document.getElementById("colsInput").value);
    if (newRows > 0 && newCols > 0) {
      setRows(newRows);
      setCols(newCols);
      setGrid(makeGrid(newRows, newCols));
      setBeginningGrid(makeGrid(newRows, newCols));
      setGeneration(0);
      setRunning(false);
    }
  };

  return (
    <div className="App">
      <h1>Game of Life</h1>

      {/* Resize Controls */}
      <div style={{ marginBottom: "20px" }}>
        <input id="rowsInput" type="number" defaultValue={rows} min="1" />
        <input id="colsInput" type="number" defaultValue={cols} min="1" />
        <button onClick={handleResize}>Resize Grid</button>
      </div>

      {/* Grids */}
      <div style={{ display: "flex", justifyContent: "center", gap: "40px" }}>
        {/* Setup Grid */}
        <div>
          <h2>Setup Grid</h2>
          <div
            style={{ display: "grid", gridTemplateColumns: `repeat(${cols}, 40px)` }}
          >
            {beginningGrid.map((row, i) =>
              row.map((cell, j) => (
                <div
                  key={`beginning-${i}-${j}`}
                  onClick={() => handleClick(i, j, true)}
                  style={{
                    width: 40,
                    height: 40,
                    backgroundColor: cell ? "black" : "white",
                    border: "solid 1px #ccc",
                  }}
                />
              ))
            )}
          </div>
        </div>

        {/* Simulation Grid */}
        <div>
          <h2>Simulation</h2>
          <div
            style={{ display: "grid", gridTemplateColumns: `repeat(${cols}, 40px)` }}
          >
            {grid.map((row, i) =>
              row.map((cell, j) => (
                <div
                  key={`grid-${i}-${j}`}
                  onClick={() => handleClick(i, j, false)}
                  style={{
                    width: 40,
                    height: 40,
                    backgroundColor: cell ? "black" : "white",
                    border: "solid 1px #ccc",
                  }}
                />
              ))
            )}
          </div>
        </div>
      </div>

      {/* Control Buttons */}
      <div style={{ marginTop: "20px" }}>
        <button
          onClick={() => {
            if (!running) {
              setGrid(beginningGrid);
              setGeneration(0);
              runningRef.current = true;
              setRunning(true);
              runSimulation();
            } else {
              runningRef.current = false;
              setRunning(false);
            }
          }}
        >
          {running ? "Stop" : "Start"}
        </button>

        <button onClick={nextGen}>Next</button>

        <button
          onClick={() => {
            for (let i = 0; i < 23; i++) nextGen();
          }}
        >
          +23 Generations
        </button>

        <button
          onClick={() => {
            setGrid(makeGrid(rows, cols));
            setBeginningGrid(makeGrid(rows, cols));
            setGeneration(0);
            setRunning(false);
          }}
        >
          Reset
        </button>

        {/* Save/Load */}
        <div style={{ marginTop: "10px" }}>
          <input id="saveName" placeholder="Save name" />
          <button
            onClick={() => {
              const name = document.getElementById("saveName").value;
              saveGrid(name);
            }}
          >
            Save Setup
          </button>

          <input id="loadName" placeholder="Load name" />
          <button
          onClick={async () => {
            const name = document.getElementById("loadName").value.trim();
            if (name) {
              await loadGrid(name);
            } else {
              alert("Please enter a name to load.");
            }
          }}
        >
          Load Setup
        </button>

        </div>
      </div>

      <h3>Generation: {generation}</h3>
    </div>
  );
}

export default App;
