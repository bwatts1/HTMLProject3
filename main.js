import React, { useState, useEffect, useRef } from 'react';

const cols = 10;
const rows = 10;
const cellSize = 40; // px

const makeGrid = () => {
  const grid = [];
  for (let i = 0; i < cols; i++) {
    const row = [];
    for (let j = 0; j < rows; j++) {
      row.push(0);
    }
    grid.push(row);
  }
  return grid;
};

const cloneGrid = (grid) => grid.map(row => [...row]);

const GameOfLife = () => {
  const [grid, setGrid] = useState(makeGrid());
  const [startGrid, setStartGrid] = useState(null);
  const [running, setRunning] = useState(false);
  const [generation, setGeneration] = useState(0);
  const [saveName, setSaveName] = useState('');
  const [loadName, setLoadName] = useState('');
  const intervalRef = useRef(null);

  const toggleCell = (i, j) => {
    const newGrid = cloneGrid(grid);
    newGrid[i][j] = grid[i][j] ? 0 : 1;
    setGrid(newGrid);
  };

  const checkNeighbors = (grid, x, y) => {
    let neighbors = 0;
    for (let i = -1; i <= 1; i++) {
      for (let j = -1; j <= 1; j++) {
        if (i === 0 && j === 0) continue;
        const newX = x + i;
        const newY = y + j;
        if (newX >= 0 && newX < cols && newY >= 0 && newY < rows) {
          neighbors += grid[newX][newY];
        }
      }
    }
    return neighbors;
  };

  const nextGeneration = () => {
    setGrid(prevGrid => {
      const newGrid = makeGrid();
      for (let i = 0; i < cols; i++) {
        for (let j = 0; j < rows; j++) {
          const neighbors = checkNeighbors(prevGrid, i, j);
          if (prevGrid[i][j]) {
            newGrid[i][j] = neighbors === 2 || neighbors === 3 ? 1 : 0;
          } else {
            newGrid[i][j] = neighbors === 3 ? 1 : 0;
          }
        }
      }
      return newGrid;
    });
    setGeneration(g => g + 1);
  };

  const start = () => {
    if (!running) {
      setStartGrid(cloneGrid(grid)); // Save the starting grid
      setRunning(true);
      intervalRef.current = setInterval(nextGeneration, 500);
    }
  };

  const stop = () => {
    setRunning(false);
    clearInterval(intervalRef.current);
  };

  const reset = () => {
    setRunning(false);
    clearInterval(intervalRef.current);
    setGrid(makeGrid());
    setStartGrid(null);
    setGeneration(0);
  };

  const saveGrid = () => {
    if (!saveName.trim()) {
      alert('Enter a save name!');
      return;
    }
    const gridString = JSON.stringify(grid);
    localStorage.setItem(`gol_${saveName}`, gridString);
    alert(`Grid saved as "${saveName}"`);
  };

  const loadGrid = () => {
    if (!loadName.trim()) {
      alert('Enter a load name!');
      return;
    }
    const gridString = localStorage.getItem(`gol_${loadName}`);
    if (gridString) {
      const loadedGrid = JSON.parse(gridString);
      setGrid(loadedGrid);
      setStartGrid(null);
      setGeneration(0);
      alert(`Grid "${loadName}" loaded.`);
    } else {
      alert('Save not found!');
    }
  };

  useEffect(() => {
    return () => clearInterval(intervalRef.current);
  }, []);

  return (
    <div style={{ padding: '20px' }}>
      <h1>Game of Life (React Version)</h1>

      <div style={{ display: 'flex', gap: '20px' }}>
        {/* Current Grid */}
        <div>
          <h2>Current Grid</h2>
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: `repeat(${cols}, ${cellSize}px)`,
              border: '1px solid black'
            }}
          >
            {grid.flatMap((row, i) =>
              row.map((cell, j) => (
                <div
                  key={`${i}-${j}`}
                  onClick={() => toggleCell(i, j)}
                  style={{
                    width: cellSize,
                    height: cellSize,
                    backgroundColor: cell ? 'black' : 'white',
                    border: '1px solid #ccc'
                  }}
                />
              ))
            )}
          </div>
        </div>

        {/* Starting Grid */}
        {startGrid && (
          <div>
            <h2>Starting Grid</h2>
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: `repeat(${cols}, ${cellSize}px)`,
                border: '1px solid black'
              }}
            >
              {startGrid.flatMap((row, i) =>
                row.map((cell, j) => (
                  <div
                    key={`${i}-${j}`}
                    style={{
                      width: cellSize,
                      height: cellSize,
                      backgroundColor: cell ? 'black' : 'white',
                      border: '1px solid #ccc'
                    }}
                  />
                ))
              )}
            </div>
          </div>
        )}
      </div>

      <br />
      <div style={{ marginBottom: '10px' }}>
        <button onClick={start} disabled={running}>Start</button>
        <button onClick={stop} disabled={!running}>Stop</button>
        <button onClick={nextGeneration}>Next</button>
        <button onClick={reset}>Reset</button>
        <p>Generation: {generation}</p>
      </div>

      <div>
        <input
          type="text"
          placeholder="Save Name"
          value={saveName}
          onChange={(e) => setSaveName(e.target.value)}
          style={{ marginRight: '5px' }}
        />
        <button onClick={saveGrid}>Save</button>
      </div>

      <div style={{ marginTop: '10px' }}>
        <input
          type="text"
          placeholder="Load Name"
          value={loadName}
          onChange={(e) => setLoadName(e.target.value)}
          style={{ marginRight: '5px' }}
        />
        <button onClick={loadGrid}>Load</button>
      </div>
    </div>
  );
};

export default GameOfLife;
